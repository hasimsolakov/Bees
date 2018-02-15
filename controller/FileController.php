<?php 
class FileController {
    private $uploadsDir;
    
    function __construct() {
        $this->uploadsDir = getcwd() . DIRECTORY_SEPARATOR . "uploads";
    }
    
    public function indexAction($view, $params) {
        $view->name = "Files";
        $files = scandir($this->uploadsDir);
        $view->files = array();
        for($i = 0; $i < count($files); $i++) {
            $filePath = $this->uploadsDir . DIRECTORY_SEPARATOR . $files[$i];
            if(is_file($filePath)) {
                $view->files[] = array("filename" => $files[$i], "size" => filesize($filePath), "file_path" => $filePath);
            }
        }
    }
    
    public function showAction($view, $params) {
        $view->name = "Show";
        $filePath = trim($_GET['file']);
        $view->file_exists = file_exists($filePath);
        if($view->file_exists) {
            $view->content = file_get_contents($filePath);
            $view->size = filesize($filePath);
            $view->file_path = $filePath;
            
            $view->matches = array();
            $questionAnswerMatches = array();
            $pattern1 = '/(?<questionanswer>\d+\)(.+\n+)+?(?=[0-9]\)|[1-9][0-9]\)|[1-9][0-9][0-9]\)|$))/';
            $pattern2 = '/(?<questionanswer>\d+\.(.+\n+)+?(?=[0-9]\.|[1-9][0-9]\.|[1-9][0-9][0-9]\.|$))/';
            preg_match_all($pattern1, $view->content, $questionAnswerMatches);//
            $questionAnswers = $questionAnswerMatches['questionanswer'];
            $view->matches = array();
            for($i = 0; $i < count($questionAnswers); $i++) {
                $questionAnswer = $questionAnswers[$i];

                $matches = array();
                $answerQPattern1 = '/(?<question>(?<=[0-9]\)|[1-9][0-9]\)|[1-9][0-9][0-9]\))(.+\n+)+?(?=a\.))(?<answers>a\.(.+\n+)+?(?=[0-9]\)|[1-9][0-9]\)|[1-9][0-9][0-9]\)|$))/';
                $answerQPattern2 = '/(?<question>(?<=[0-9]\.|[1-9][0-9]\.|[1-9][0-9][0-9]\.)(.+\n+)+?(?=[a-g]\.))(?<answers>[a-g]\.(.+\n+)+?(?=[0-9]\.|[1-9][0-9]\.|[1-9][0-9][0-9]\.|$))/';
                preg_match_all($answerQPattern1, $questionAnswer, $matches);
                $question = $matches['question'];
                $answers = $matches['answers'];
                $answerMatches = array();
                preg_match_all('/(?<answer>[\w]\..+)/', $answers[0], $answerMatches);
                $answersArray = $answerMatches['answer'];
                $answers = array();
                for($j = 0; $j < count($answersArray); $j++) {
                    $correctMatches = array();
                    $isCorrect = preg_match('/\w\..+?(?=\sCorrect|\s-\sCorrect|-correct|-\scorrect)/', $answersArray[$j], $correctMatches);
                    if($isCorrect) {
                        $answers[] = array("text" => $correctMatches[0], "isCorrect" => true);
                    } else {
                        $answers[] = array("text" => $answersArray[$j], "isCorrect" => false);
                    }

                }
                
                $view->matches[] = array("question" => $question[0], "answers" => $answers);
            }
            
            $this->saveMatches($view->matches);
        }
    }
    
    private function saveMatches($matches) {
        $connection = $this->getConnection();
        $connection->beginTransaction();
        for($i = 0; $i < count($matches); $i++) {
            $stmt = $connection->prepare("INSERT INTO question(question) VALUES(?)");
            $stmt->execute(array($matches[$i]['question']));
            $insertedOK = $stmt->rowCount() === 1;
            if(!$insertedOK) {
                var_dump($i);
                var_dump($matches[$i]['question']);
                $connection->rollback();
                echo "Could not insert!";
                exit;
            }
            
            $questionId = $connection->lastInsertId();
           // var_dump($matches[$i]['answers']);
            $insertAnswersValues = "";
            $params = array();
            for($j = 0; $j < count($matches[$i]['answers']); $j++) {
                $insertAnswersValues .= "(:question_id_$j, :answer_$j, :is_correct_$j),";
                $params["question_id_$j"] = $questionId;
                $params["answer_$j"] = $matches[$i]['answers'][$j]['text'];
                $params["is_correct_$j"] = $matches[$i]['answers'][$j]['isCorrect'];
            }
            
            $insertAnswersValues = substr($insertAnswersValues, 0, strlen($insertAnswersValues) - 1);
            // var_dump($insertAnswersValues);
            // var_dump($params);
            $stmt = $connection->prepare("INSERT INTO answer(question_id, answer, is_correct) VALUES $insertAnswersValues");
            $stmt->execute($params);
            $rowCount = $stmt->rowCount();
            $insertedOK = $rowCount === count($matches[$i]['answers']);
            if(!$insertedOK) {
                echo "Could not insert Answers";
                $connection->rollback();
                exit;
            }
        }
        
        $connection->commit();
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
        $connection = getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }
    
    public function uploadAction($view, $params) {
        $view->name = "Upload";
    }
    
    public function parseAction($view, $params) {
        $view->delete = isset($_GET['delete']) ? $_GET['delete'] : false;
       if(empty($_FILES['file']['tmp_name']) && !$view->delete) {
            header("Location: http://bees.cloudvps.bg/file/upload");
            exit;
        } 

        if($view->delete) {
            $view->file_exists = file_exists($view->delete);
            if($view->file_exists) {
                $wasDeleted = unlink($view->delete);
                $view->deletedOK = $wasDeleted;
            }
            
            $view->name = "Delete";
        } else {
            $view->content = file_get_contents($_FILES['file']['tmp_name']);
            $view->name = "Parse";
            $view->tempname = tempnam('uploads', "programming-test");
            $view->written = file_put_contents($view->tempname, $view->content);
        }
    }
}
?>