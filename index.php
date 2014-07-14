<?php
require 'Slim/Slim.php';
require 'Brainly/JValidator/Validator.php'; // Using JSON schema validator from https://github.com/brainly/jvalidator
\Slim\Slim::registerAutoloader();

$validator = new Brainly\JValidator\Validator();
$schema = file_get_contents('json/schema.json');

$app = new \Slim\Slim(array(
"MODE" => "development",
"debug" => true
));

$app->get('/','home');

$app->get('/question/:id', 'getQuestion');
$app->put('/question/:id', 'updateQuestion');
$app->delete('/question/:id', 'deleteQuestion');
$app->get('/questions', 'getQuestions');
$app->post('/questions', 'createQuestion');


$app->get('/answer/:id', 'getAnswer');
$app->put('/answer/:id', 'updateAnswer');
$app->get('/question/:id/answers', 'getAnswerForQuestion');
$app->post('/question/:id/answers', 'createAnswertoQuestion');
$app->delete('/question/:id/answers', 'removeAnswertoQuestion');

$app->run();

function home() {
	try {
		$db = getConnection();
		echo json_encode(array("message"=>"System is ready"));
	} catch (PDOException $e) {		
		echo json_encode(array("code"=>$e->getCode(), "error"=>$e->getMessage()));
	}	
}


function getQuestions() {
	global $app;
	
	$sql = "SELECT id, name, responses, type FROM questions";
	try {
		$db = getConnection();
		$query = $db->query($sql);
		$questions = $query->fetchAll(PDO::FETCH_OBJ);
		$db = null;		
		$app->response()->status(201);
		
		// convert array string from mysql to json object
		foreach ($questions as &$q) {
			$q->responses = json_decode($q->responses);
			foreach ($q->responses as &$response) {
				$response = json_decode($response);
			}
		}
		echo json_encode($questions);
		
	} catch (PDOException $e) {
		echo '{"error": {"text":'. $e->getMessage().'}}';
	}
}

function createQuestion() {

	$id = md5(microtime().rand());	
	
	global $app, $validator, $schema;
	$json = $app->request->getBody();
    $validator->validate($json, $schema);

    if ($validator->getResultCode() != 0) {
	    echo json_encode($validator->getValidationErrors()); die();
    }
	
	$request = json_decode($app->request->getBody());
	
	// fix schema validation error that "responses" has to be "String"
	if (!empty($request->responses)) :
		$response = array();	
		foreach ($request->responses as $r) {
			$response[] = json_encode($r);
		}
		$request->responses = $response;
		
		// save "responses" in json format to mysql db
		$responses = json_encode($request->responses);	
	endif;
	

	
    $sql = "INSERT INTO questions (id, name, type, responses, placeholder) VALUES (:id, :name, :type, :responses, :placeholder)";
	try {
		$db = getConnection();
		$query = $db->prepare($sql);
		$query->bindParam("name", $request->name);
		$query->bindParam("type", $request->type);
		$query->bindParam("responses", $request->responses);
		$query->bindParam("placeholder", $request->placeholder);
		$query->bindParam("id", $id);
		$query->execute();
				
		$db = null;
		$app->response()->status(201);
		echo json_encode(array("id"=>$id, "name"=>$request->name, "type"=>$request->type));
	} catch (PDOException $e) {
		echo '{"error": {"text":'. $e->getMessage().'}}';
	}
}


function getQuestion($id) {
	
    $sql = "SELECT type, responses, placeholder FROM questions WHERE id = :id";
	try {
		$db = getConnection();
		$query = $db->prepare($sql);
		$query->bindParam("id", $id);
		
		$query->execute();
		
		$question = $query->fetchObject();
		$db = null;
		
		echo json_encode($question);
		

	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function updateQuestion($id) {

	global $app, $validator, $schema;	
    
	$request = json_decode($app->request->getBody());
	
	if (!empty($request->responses) && is_array($request->responses) ) :
		// fix schema validation error that "responses" has to be "String"
		$response = array();	
		foreach ($request->responses as $r) {
			$response_id = md5(microtime().rand());
			$r->id = $response_id;
			$r->question_id = $id;
			$response[] = json_encode($r);
		}
		$request->responses = $response;	
		
		// save "responses" in json format to mysql db
		$responses = json_encode($request->responses);
	endif;
	
	// validate json
	$json = json_encode($request);
    $validator->validate($json, $schema);

    if ($validator->getResultCode() != 0) {
	    echo json_encode($validator->getValidationErrors()); die();
    }	
    // end validation
			
	$sql = "UPDATE questions SET 
				name = IF (:name IS NOT NULL, :name, name), 
				type = IF (:type IS NOT NULL, :type, type), 
				responses = IF (:responses IS NOT NULL, :responses, responses), 
				placeholder = IF (:placeholder IS NOT NULL, :placeholder, placeholder) 
			WHERE id = :id";
	try {
		$db = getConnection();
		$query = $db->prepare($sql);
		$query->bindParam("name", $request->name);
		$query->bindParam("type", $request->type);
		$query->bindParam("responses", $responses);
		$query->bindParam("placeholder", $request->placeholder);
		$query->bindParam("id", $id);
		//$status = $query->execute();
				
		$db = null;
		//echo json_encode(array("status"=>$status));
		
	} catch (PDOException $e) {
		echo '{"error": {"text":'. $e->getMessage().'}}';
	}

}

function deleteQuestion($id) {
	global $app;
	$sql = "DELETE FROM questions WHERE id = :id";
	try {
		$db = getConnection();
		$query = $db->prepare($sql);
		$query->bindParam("id", $id);
		$stat = $query->execute();	
		
		$db=null;
		$app->response()->status(204);
		echo json_encode($stat);
		
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
	
}


function getAnswer($id) {
	global $app;
	
	$sql = "SELECT text, type FROM answers WHERE id = :id";
	try {
		$db = getConnection();
		$query = $db->prepare($sql);
		$query->bindParam("id", $id);
		
		$query->execute();
		
		$answer = $query->fetchObject();
		$db = null;
		$app->response()->status(201);
		echo json_encode($answer);
		

	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function updateAnswer($id) {
	global $app;	
    
   
	$request = json_decode($app->request->getBody());
					
	$sql = "UPDATE answers SET text = :text WHERE id = :id";
	try {
		$db = getConnection();
		$query = $db->prepare($sql);
		$query->bindParam("text", $request->text);
		$query->bindParam("id", $id);
		//$status = $query->execute();
				
		$db = null;
		$app->response()->status(201);
		//echo json_encode(array("status"=>$status));
		
	} catch (PDOException $e) {
		echo '{"error": {"text":'. $e->getMessage().'}}';
	}

}

function getAnswerForQuestion($question_id) {
	$sql = "SELECT question_id, text, id, type 
			FROM answers	
			WHERE question_id = :id";
	try {
		$db = getConnection();
		$query = $db->prepare($sql);
		$query->bindParam("id", $question_id);
		
		$query->execute();
		
		$answer = $query->fetchAll(PDO::FETCH_CLASS);
		$db = null;
		
		echo json_encode($answer);
		

	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function createAnswertoQuestion($id) {
	global $app;
	
	$answer_id = md5(microtime().rand());
	
	$request = json_decode($app->request->getBody());
	
	$sql = "INSERT INTO answers (id, text, type, question_id) VALUES (:id, :text, :type, :question_id)";
	try {
		$db = getConnection();
		$query = $db->prepare($sql);
		$query->bindParam("text", $request->text);
		$query->bindParam("type", $request->type);
		$query->bindParam("question_id", $id);
		$query->bindParam("id", $answer_id);
		$query->execute();
				
		$db = null;
		$app->response()->status(201);
		echo json_encode(array("text"=>$request->text, "id"=>$answer_id, "type"=>$request->type));
		
		
	} catch (PDOException $e) {
		echo '{"error": {"text":'. $e->getMessage().'}}';
	}

	
}

function removeAnswertoQuestion($id) {
	global $app;
	$sql = "DELETE FROM answers WHERE question_id = :id";
	try {
		$db = getConnection();
		$query = $db->prepare($sql);
		$query->bindParam("id", $id);
		$stat = $query->execute();	
		
		$db=null;
		$app->response()->status(201);
		echo json_encode($stat);
		
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function getConnection() {
	$dbhost="localhost";
	$dbuser="root";
	$dbpass="root";
	$dbname="h2wellness";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);	
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbh;
}