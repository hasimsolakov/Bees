<?php 
class IndexController {
    public function indexAction() {
            $stmt = $this->queryList("SELECT MAX(Q.id) as id_to_preserve, Q.question FROM question AS Q
            INNER JOIN (SELECT GQ.question, COUNT(GQ.id) as dublicatesCount FROM question AS GQ WHERE GQ.is_deleted = 0 GROUP BY GQ.question) AS D
            ON Q.question = D.question
            WHERE D.dublicatesCount > 1
            GROUP BY Q.question;", array());
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($results as $questionToKeep) {
                $questionIdToKeep = $questionToKeep['id_to_preserve'];
                $questionText = $questionToKeep['question'];
                $selectDublicatesStmt = $this->queryList("SELECT id FROM question WHERE id != :id AND question IN (SELECT Q.question FROM question AS Q WHERE Q.id = :id)", array("id" => $questionIdToKeep));
                $dublicates = $selectDublicatesStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach($dublicates as $dublicate) {
                    $updateStmt = $this->queryList("UPDATE question SET is_deleted = 1 WHERE id = :question_id", array("question_id" => $dublicate['id']));
                    $updatedRows = $updateStmt->rowCount();
                    echo "Updated rows: " . var_export($updatedRows, true) . "<br>";
                }
            }
    }
    
    public function deleteAction($view) {
        $questionId = $_POST['question_id'];
        $password = $_POST['pass'];
        if($password === "protected_by_me") {
                    $stmt = $this->queryList("UPDATE question SET is_deleted = 1 WHERE id = :question_id", array("question_id" => $questionId));
            echo $stmt->rowCount();

        } else {
            echo "You can not delete!";
        }
            exit;
    }
    
    public function listAction($view) {
        $stmt = $this->queryList("SELECT Q.id as question_id, Q.question, A.id as answer_id, A.question_id as answer_question_id, A.is_correct, A.answer FROM question AS Q INNER JOIN answer AS A ON A.question_id = Q.id  WHERE Q.is_deleted = 0 ORDER BY question", array());
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $questions = array();
        $answers = array();
        foreach($results as $questionAnswer) {
            $questions [$questionAnswer['question_id']] = array("id" => $questionAnswer['question_id'], "question" => $questionAnswer['question']);
            if(!isset($answers[$questionAnswer['question_id']])) {
                $answers[$questionAnswer['question_id']] = array();
            }
            
            $answers[$questionAnswer['answer_question_id']][] = array("text" => $questionAnswer['answer'], "is_correct" => $questionAnswer['is_correct']);
        }
        
        $view->questions = $questions;
        usort($view->questions, array('IndexController', 'sortQuestions'));
        

        $view->answers = $answers;
        $view->name = "Questions";
    }
    
    static function sortQuestions($a, $b) {
        return strcmp($a['question'], $b['question']);
    }
    
    private function getConnection(){
        $username="beeslbsr_bees";
        $password="b33srocktheworld";
        $host="localhost";
        $db="beeslbsr_bees_environment";
        $connection = new PDO("mysql:dbname=$db;host=$host", $username, $password);
        return $connection;
    }
    
    private function queryList($sql, $args){
        $connection = $this->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }
}
?>