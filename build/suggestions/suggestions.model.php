<?php
/**
 * suggestions model class.
 *
 * @author shevek
 */
class SuggestionsModel extends RoxModelBase
{
    const SUGGESTIONS_DUPLICATE = 0; // suggestion already existed and was there marked as duplicate by suggestion team
    const SUGGESTIONS_AWAIT_APPROVAL = 1; // wait for suggestion team to check
    const SUGGESTIONS_DISCUSSION = 2; // discuss the suggestion try to find solutions
    const SUGGESTIONS_ADD_OPTIONS = 4; // enter solutions into the system (10 days after start)
    const SUGGESTIONS_VOTING = 8; // allow voting (30 days after switching to discussion mode)
    const SUGGESTIONS_RANKING = 16; // Voting finished (30 days after voting started). Ranking can be done now.
    const SUGGESTIONS_REJECTED = 32; // Suggestion didn't reach the necessary level of approval (at least 'good')
    const SUGGESTIONS_IMPLEMENTING = 64; // Dev started implementing (no more ranking)
    const SUGGESTIONS_IMPLEMENTED = 128; // Dev finished implementation
    const SUGGESTIONS_DEV = 192;

    public function getSuggestionsCount($type) {
        if (!is_numeric($type) && !is_int($type)) {
            return -1;
        }

        switch($type) {
            case self::SUGGESTIONS_REJECTED:
                $query = "SELECT COUNT(*) FROM suggestions WHERE state = "
                    . self::SUGGESTIONS_REJECTED
                    . " OR state = " . self::SUGGESTIONS_DUPLICATE;
                break;
            case self::SUGGESTIONS_DEV:
                $query = "SELECT COUNT(*) FROM suggestions WHERE state = "
                    . self::SUGGESTIONS_IMPLEMENTED
                    . " OR state = " . self::SUGGESTIONS_IMPLEMENTING;
                break;
            default:
                $query = "SELECT COUNT(*) FROM suggestions WHERE state = " . $type;
                break;
        }
        $sql = $this->dao->query($query);
        if (!$sql) {
            return false;
        }
        $row = $sql->fetch(PDB::FETCH_NUM);
        return $row[0];
    }

    public function getSuggestions($type, $pageno, $items) {
        if (!is_numeric($type) && !is_int($type)) {
            return false;
        }
        $temp = $this->CreateEntity('Suggestion');
        switch($type) {
            case self::SUGGESTIONS_REJECTED:
                $query = "state = " . self::SUGGESTIONS_REJECTED
                    . " OR state = " . self::SUGGESTIONS_DUPLICATE;
                $temp->sql_order = "state DESC, created ASC";
                break;
            case self::SUGGESTIONS_DEV:
                $query = "state = " . self::SUGGESTIONS_IMPLEMENTED
                    . " OR state = " . self::SUGGESTIONS_IMPLEMENTING;
                $temp->sql_order = "state ASC, created ASC";
                break;
            default:
                $query = "state = " . $type;
                $temp->sql_order = "created ASC";
                break;
        }
        $all = $temp->FindByWhereMany($query, $pageno * $items, $items);
        return $all;
    }

    public function checkEditCreateSuggestionVarsOk($args) {
        $errors = array();
        $vars = $args->post;
        return $errors;
    }

    public function createSuggestion($args) {
        $suggestion = new Suggestion;
        $suggestion->summary = $args->post['suggestion-summary'];
        $suggestion->description = $args->post['suggestion-description'];
        $suggestion->state = SuggestionsController::SUGGESTIONS_AWAIT_APPROVAL;
        $suggestion->salt = hash_hmac('sha256', $suggestion->description, $suggestion->summary);
        $suggestion->created = date('Y-m-d');
        $suggestion->createdby = $this->getLoggedInMember()->id;
        $suggestion->insert();
        return $suggestion;
    }

    public function editSuggestion($args) {
        $suggestion = new Suggestion($args->post['suggestion-id']);
        $suggestion->summary = $args->post['suggestion-summary'];
        $suggestion->description = $args->post['suggestion-description'];
        $suggestion->modified = date('Y-m-d');
        $suggestion->modifiedby = $this->getLoggedInMember()->id;
        $suggestion->update();
        return $suggestion;
    }

    public function approveSuggestion($id) {
        $suggestion = new Suggestion($id);
        $suggestion->state = self::SUGGESTIONS_DISCUSSION;
        $suggestion->modified = date('Y-m-d');
        $suggestion->modifiedby = $this->getLoggedInMember()->id;
        $suggestion->update();
    }

    public function markDuplicateSuggestion($id) {
        $suggestion = new Suggestion($id);
        $suggestion->state = self::SUGGESTIONS_DUPLICATE;
        $suggestion->modified = date('Y-m-d');
        $suggestion->modifiedby = $this->getLoggedInMember()->id;
        $suggestion->update();
    }

    public function getVotesForLoggedInMember($suggestion) {
        $member = $this->getLoggedInMember();
        $hash = hash_hmac('sha256', $member->id, $suggestion->salt);
        $query = "SELECT * FROM suggestions_votes WHERE suggestionId = "
            . $suggestion->id . " AND memberHash = '" . $hash . "'";
        $sql = $this->dao->query($query);
        if (!$sql) {
            return false;
        }
        $votes = array();
        while($row = $sql->fetch(PDB::FETCH_OBJ)) {
            $votes[$row->optionId] = $row;
        }
        return $votes;
    }

    public function checkVoteSuggestion($vars) {
        return array();
    }

    private function filterOptions($var) {
        if (!is_string($var)) {
            return false;
        }
        $pos = strpos($var, 'option');
        if ($pos !== false) {
            return true;
        }
        return false;
    }

    public function voteForSuggestion($member, $args) {
        $optionKeys = array_filter(array_keys($args->post), array($this, 'filterOptions'));
        $suggestion = new Suggestion($args->post['suggestion-id']);
        $member = $this->getLoggedInMember();
        $hash = hash_hmac('sha256', $member->id, $suggestion->salt);

        // Initialize votes with 'poor'
        $votes = $this->getVotesForLoggedInMember($suggestion);
        if (empty($votes)) {
            foreach($suggestion->options as $option) {
                $vote = new StdClass;
                $vote->id = 0;
                $vote->suggestionId = $suggestion->id;
                $vote->optionId = $option->id;
                $vote->rank = 1;
                $vote->memberHash = $hash;
                $votes[$option->id] = $vote;
            }
        }

        foreach($optionKeys as $key) {
            $optionId = str_replace(array("option", "rank"), "", $key);
            $rank = $args->post[$key];
            $votes[$optionId]->rank = $rank;
        }

        foreach($votes as $vote) {
            if ($vote->id == 0) {
                $query = "INSERT INTO suggestions_votes SET suggestionId = " . $suggestion->id
                    . ", optionId = " . $vote->optionId . ", rank = " . $vote->rank
                    . ", memberHash = '" . $vote->memberHash . "'";
            } else {
                $query = "REPLACE INTO suggestions_votes SET id = " . $vote->id
                    . ", suggestionId = " . $vote->suggestionId . ", optionId = "
                    . $vote->optionId . ", rank = " . $vote->rank . ", memberHash = '" . $vote->memberHash . "'";
            }
            $this->dao->query($query);
        }

        return $suggestion;
    }
}

?>