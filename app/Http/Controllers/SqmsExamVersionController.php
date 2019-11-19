<?php

namespace App\Http\Controllers;

use App\Sqms_exam_version;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
#use WindowsAzure\Common\ServicesBuilder;
#use WindowsAzure\Common\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;# imports
use MicrosoftAzure\Storage\Blob\BlobRestProxy;



class SqmsExamVersionController extends Controller
{

    //private static $tableName = "sqms_exam_version";
    private static $tableName = "v_sqms_exam_version_sample_set";

    public function index()
    {
        return Sqms_exam_version::all();
    }

    public function getconnection(){
        try {
            DB::connection()->getPdo();
            if(DB::connection()->getDatabaseName()){
                return response()->json([
                    "message" => DB::connection()->getDatabaseName(),
                    "status" => true

                ], 200);
            }else{
                return response()->json([
                    "message" => "Could not find the database. Please check your configuration.",
                    "status" => false

                ],400);
            }
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Could not open connection to database server.  Please check your configuration.",
                "status" => false
            ], 400);

        }

    }

    public function hashsalt()
    {
        return response()->json(config('constants.hash_salt'));
    }

    protected function  showMore($data, $hash_salt, $successpercent)
    {
        $idvcsv = '';
        foreach ($data as $k => $v) {
            $onev = explode("|", $v);
            $idv[] = (int) $onev[0];
            $idvcsv .= $onev[0] . ',';
        }
        asort($idv);

        $queryExams = DB::table(self::$tableName)->whereIn('sqms_exam_version_id', $idv)->get();
        //$queryExams = DB::table('sqms_exam_version')->whereIn('sqms_exam_version_id', $idv)->get();

        $examNamefull = "COMBI ";
        $idset = '';
        $i = 0;

        foreach ($queryExams as $k => $v) {
            $v->sqms_exam_version_id;
            $examNamefull .= $this->striphtml($v->sqms_exam_version_name) . "($i), ";

            $sqms_exam_version_id = sprintf("%010d", $v->sqms_exam_version_id);
            $sqms_exam_set = sprintf("%05d", $v->sqms_exam_set);
            $sqms_exam_version = sprintf("%05d", $v->sqms_exam_version);
            $sqms_exam_version_sample_set = $v->sqms_exam_version_sample_set;
            $idset .= $sqms_exam_version_id . '-' . $sqms_exam_set . '-' . $sqms_exam_version . '-' . $sqms_exam_version_sample_set . '-';
            $i++;
        }

        return $this->showAdv($idvcsv, $examNamefull, $idset, $hash_salt, $v->sqms_exam_set, $v->sqms_exam_version, $sqms_exam_version_sample_set, $successpercent);

    }

    protected function striphtml($value){

        $allowed = "<div><span><pre><p><br><hr><hgroup><h1><h2><h3><h4><h5><h6>";
        $allowed .= "<ul><ol><li><dl><dt><dd><strong><em><b><i>";
        $allowed .= "<img><a><abbr><address><blockquote><area><audio><video>";
        $allowed .= "<caption><table><tbody><td><tfoot><th><thead><tr><sup><sub>";

        return  htmlspecialchars(strip_tags($value,$allowed));
    }

    protected function generateXMLMore($data, $hash_salt)
    {
        $idvcsv = '';
        foreach ($data as $k => $v) {
            $onev = explode("|", $v);
            $idv[] = $onev[0];
            $idvcsv .= $onev[0] . ',';
        }

        $queryExams = DB::table(self::$tableName)->whereIn('sqms_exam_version_id', $idv)->get();
        //$queryExams = DB::table('sqms_exam_version')->whereIn('sqms_exam_version_id', $idv)->get();

        $domtree = new \DOMDocument('1.0', 'UTF-8');
        $xmlRoot = $domtree->createElement("quiz");
        $xmlRoot = $domtree->appendChild($xmlRoot);

        $examNamefull = "COMBI ";

        $idset = '';
        $i = 0;
        foreach ($queryExams as $k => $v) {
            $examNamefull .= $this->striphtml($v->sqms_exam_version_name) . "($i), ";

            $sqms_exam_version_id = sprintf("%010d", $v->sqms_exam_version_id);
            $sqms_exam_set = sprintf("%05d", $v->sqms_exam_set);
            $sqms_exam_version = sprintf("%05d", $v->sqms_exam_version);
            $sqms_exam_version_sample_set = $v->sqms_exam_version_sample_set;
            $idset .= $sqms_exam_version_id . '-' . $sqms_exam_set . '-' . $sqms_exam_version . '-' . $sqms_exam_version_sample_set . '-';

            $i++;
        }

        $xmlRoot->appendChild($domtree->createElement('examName', $examNamefull));
        $xmlRoot->appendChild($domtree->createElement('set', $v->sqms_exam_set));
        $xmlRoot->appendChild($domtree->createElement('version', $v->sqms_exam_version));
        $xmlRoot->appendChild($domtree->createElement('SampleSet', $v->sqms_exam_version_sample_set));
        $xmlRoot->appendChild($domtree->createElement('id', rtrim($idset, "-")));

        $numberOfQuestionTotal = $this->numberOfQuestionTotal($idvcsv);

        $xmlRoot->appendChild($domtree->createElement('questionTotal', $numberOfQuestionTotal[0]->questionTotal));

        // ADD COMMENT IN QUIZ
        $commentlink = "\n \"examName \" : " . "\"" . $v->sqms_exam_version_name . "\" \n";
        $commentlink .= "\"set \" : " . $v->sqms_exam_set . " \n";
        $commentlink .= "\"version \" : " . $v->sqms_exam_version . " \n";
        $commentlink .= "\"SampleSet \" : " . $v->sqms_exam_version_sample_set . " \n";
        $commentlink .= "\"id \" : " . "\"" . rtrim($idset, "-") . "\" \n";
        $commentlink .= "\"questionTotal \" : " . $numberOfQuestionTotal[0]->questionTotal . " \n";

        $comment = $domtree->createComment($commentlink);
        $xmlRoot->appendChild($comment);


        $numberOfQuestionTotalExam = DB::select("CALL examquestions('" . rtrim($idvcsv, ", ") . "')");
        foreach ($numberOfQuestionTotalExam as $k => $v) {

            $sprint_sqms_question_id = sprintf("%010d", $v->sqms_question_id);
            $qarr['question_id'] = $sprint_sqms_question_id;
            $qarr['question_text'] = $v->question;
            $qarr['answers'] = [];
            $qarr['answersSelected'] = [];

            $question_question = $v->question;

            $answer_is_sprint = '';
            $listanswers = DB::select("CALL listanswers($v->sqms_exam_version_id,$v->sqms_question_id)");
            if (count($listanswers) > 0) {
                foreach ($listanswers as $k => $v) {
                    $answer_is_sprint .= '-' . sprintf("%010d", $v->sqms_answer_id);
                }
            }

            // QUESTION
            $currentTrack = $domtree->createElement("question");
            $currentTrack = $xmlRoot->appendChild($currentTrack);
            $type = $domtree->createAttribute("type");
            $currentTrack->appendChild($type);
            $multichoiseset = $domtree->createTextNode("multichoiceset");
            $type->appendChild($multichoiseset);

            // NAME
            $name = $currentTrack->appendChild($domtree->createElement('name'));
            $text = $domtree->createElement("text");
            $name->appendChild($text);
            $text->appendChild($domtree->createCDATASection($sprint_sqms_question_id . $answer_is_sprint));

            // QUESTIONTEXT
            $questiontext = $currentTrack->appendChild($domtree->createElement('questiontext'));
            $format = $domtree->createAttribute("format");
            $questiontext->appendChild($format);
            $html = $domtree->createTextNode("html");
            $format->appendChild($html);

            $text = $questiontext->appendChild($domtree->createElement("text"));
            $text->appendChild($domtree->createCDATASection($question_question));

            // ANSWER
            if (count($listanswers) > 0) {
                foreach ($listanswers as $k => $v) {

                    $answer = $currentTrack->appendChild($domtree->createElement('answer'));
                    $fraction = $domtree->createAttribute("fraction");
                    $answer->appendChild($fraction);
                    $numberfraction = $domtree->createTextNode("100.00000");
                    $fraction->appendChild($numberfraction);

                    $text = $answer->appendChild($domtree->createElement("text"));
                    $text->appendChild($domtree->createCDATASection($v->answer));

                }
            }
        }

        $currentTrack->appendChild($domtree->createElement('shuffleanswers', 1));
        $currentTrack->appendChild($domtree->createElement('single', false));
        $currentTrack->appendChild($domtree->createElement('answernumbering', 'abc'));

        return $domtree->saveXML();
    }

    public function show(Sqms_exam_version $sqms_exam_version, Request $request)
    {
        $datafull = $request->all();
        $data = $datafull["data"];
        $hash_salt = strtoupper($datafull["hash_salt"]);
        $savedata = $datafull["savedata"];
        $successpercent = (int) $datafull["successpercent"];

        if (!$hash_salt) {
            return response()->json([
                'message' => 'No hash_salt, please add one',
                'status' => false
            ]);
            die;
        }

        if (!$successpercent) {
            return response()->json([
                'message' => 'No success percent, please add one',
                'status' => false
            ]);
            die;
        }

        if (count($data) > 1) {
            $json = $this->showMore($data, $hash_salt, $successpercent);
            $xml = $this->generateXMLMore($data, $hash_salt);
            $linkdofile = $this->saveToStorage($savedata, $json, $xml, $hash_salt);

            return response()->json([
                'json' => $json,
                'xml' => $xml,
                'message' => 'Ok',
                'status' => true,
                'savedata' => $linkdofile
            ]);


        } else {
            $json = $this->showOne($data, $hash_salt, $successpercent);
            $xml = $this->generateXML($data, $hash_salt);
            $linkdofile = $this->saveToStorage($savedata, $json, $xml, $hash_salt);

            return response()->json([
                'json' => $json,
                'xml' => $xml,
                'message' => 'Ok',
                'status' => true,
                'savedata' => $linkdofile
            ]);

        }
    }

    protected function saveToStorage($savedata, $json, $xml, $hash_salt)
    {
        if ($savedata == 'download') {
            $namefile = preg_replace('/[^a-zA-Z0-9]+/', '_', $json["ExamVersion_Name"]);
            $publiclink = 'public/' . $namefile;
            Storage::makeDirectory($publiclink);
            Storage::put($publiclink . '/' . $namefile . '.json', json_encode($json));
            Storage::put($publiclink . '/' . $namefile . '.xml', $xml);
            Storage::put($publiclink . '/' . $namefile . '.salt', $hash_salt);

            // save JSON Questions to Azure Blob
            $this->saveToAzureBlob($publiclink,$namefile);

            // save SALT to Azure Blob
            $this->saveSaltToAzureBlob($publiclink,$namefile);

        }  else {
            $namefile = false;
        }

        return $namefile;
    }

    protected function saveSaltToAzureBlob($publiclink,$namefile){

        $connectionString = "DefaultEndpointsProtocol=http;AccountName=".env('AZURE_ACCOUNT_NAME').";AccountKey=".env('AZURE_ACCOUNT_KEY');
        //$blobRestProxy = ServicesBuilder::getInstance()->createBlobService($connectionString);
        $blobClient = BlobRestProxy::createBlobService($connectionString);

        $content = Storage::get($publiclink . '/' . $namefile . '.salt');
        $blob_name = 'salt/'.$namefile.".salt";

        try {
            $options = new CreateBlockBlobOptions();
            $contentType = 'text/plain';
            $options->setContentType($contentType);
            $blobClient->createBlockBlob(env('AZURE_CONTAINER'), $blob_name, $content,$options);
        } catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }

    }

    protected function saveToAzureBlob($publiclink,$namefile){

        $connectionString = "DefaultEndpointsProtocol=http;AccountName=".env('AZURE_ACCOUNT_NAME').";AccountKey=".env('AZURE_ACCOUNT_KEY');
        //$blobRestProxy = ServicesBuilder::getInstance()->createBlobService($connectionString);
        $blobClient = BlobRestProxy::createBlobService($connectionString);


        $content = Storage::get($publiclink . '/' . $namefile . '.json');
        $blob_name = $namefile.".json";

        try {
            $options = new CreateBlockBlobOptions();
            $contentType = 'application/json';
            $options->setContentType($contentType);
            $blobClient->createBlockBlob(env('AZURE_CONTAINER'), $blob_name, $content,$options);
        } catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }

    }

    protected function numberOfQuestionTotal($idvcsv)
    {
        return DB::select("CALL countexams('" . rtrim($idvcsv, ", ") . "')");
    }

    protected function showAdv($idvcsv, $examNamefull, $idset, $hash_salt, $sqms_exam_set, $sqms_exam_version, $sqms_exam_version_sample_set, $successpercent)
    {

        $numberOfQuestionTotal = $this->numberOfQuestionTotal($idvcsv);
        $numberOfQuestionTotalNumber = $numberOfQuestionTotal[0]->questionTotal;

        $ExamVersion_passingPoints = ceil($numberOfQuestionTotalNumber * $successpercent / 100);


        $response["ExamVersion_ID"] = "";
        $response["ExamVersion_EXTERNAL_ID"] = rand(10,100000);
        $response["ExamVersion_Name"] = rtrim($examNamefull, ", ");
        $response["ExamVersion_Set"] = $sqms_exam_set;
        $response["ExamVersion_Version"] = $sqms_exam_version;
        $response["ExamVersion_SampleSet"] = ($sqms_exam_version_sample_set) ? true : false;
        $response["ExamVersion_QuestionNumber"] = "";
        $response["Exam_SuccessPercent"] = $successpercent;
        $response["ExamVersion_maxPoints"] = $numberOfQuestionTotalNumber;
        $response["ExamVersion_passingPoints"] = $ExamVersion_passingPoints;
        $response["ExamVersion_Language"] = "de";
        $response["ExamVersion_Type"] = "static";
        $response["BulkEvent_ID"] = "";
        $response["BulkEvent_EXTERNAL_ID"] = "";
        $response["Participant_ID"] = "";
        $response["Participant_EXTERNAL_ID"] = "";
        $response["Participant_MatriculationNumber"] = "";
        $response["Participant_Firstname"] = "";
        $response["Participant_Lastname"] = "";
        $response["Participant_Expert"] = false;
        $response["ExamEvent_ID"] = "";
        $response["ExamEvent_EXTERNAL_ID"] = "";
        $response["ExamEvent_GenerationTime"] = "";
        $response["ExamEvent_ReadyTime"] = "";
        $response["ExamEvent_StartTime"] = "";
        $response["ExamEvent_EndTime"] = "";
        $response["ExamVersion_plannedDuration"] = "";
        $response["Exam_Started"] = false;
        $response["Exam_Finished"] = false;
        $response["Exam_FMR"] = "";
        $response["Exam_Id"] = rtrim($idset, "-");
        $response["Exam_Uid"] = "";
        $response["Exam_QuestionIndex"] = 0;
        $response["Exam_QuestionTotal"] = $numberOfQuestionTotalNumber;
        $response["Exam_AnswerOptions"] = [];


        $numberOfQuestionTotalExam = DB::select("CALL examquestions('" . rtrim($idvcsv, ", ") . "')");
        $tren = [];
        foreach ($numberOfQuestionTotalExam as $k => $v) {
            $sprint_sqms_question_id = sprintf("%010d", $v->sqms_question_id);
            $qarr['question_id'] = $sprint_sqms_question_id;
            $qarr['question_text'] = strip_tags(html_entity_decode($v->question, ENT_COMPAT | ENT_HTML401, 'UTF-8'));
            //$qarr['sqms_exam_version_id'] = $v->sqms_exam_version_id;
            $qarr['answers'] = [];
            $qarr['answersSelected'] = [];


            //$numberOfQuestionTotal = DB::select("CALL examquestions('" . rtrim($idvcsv, ", ") . "')");

            $ls = [];
            $listanswers = DB::select("CALL listanswers($v->sqms_exam_version_id,$v->sqms_question_id)");
            if (count($listanswers) > 0) {
                $varHashFirstNumber = true;  // remove line in prod
                $firstNumberforHash = '';
                foreach ($listanswers as $k => $v) {


                    $answer_is_sprint_int = (int) $v->sqms_answer_id;
                    $correct_answ = $v->correct;
                    $answer_is_sprint = sprintf("%010d", $v->sqms_answer_id);
                    $forls['answer_id'] = $answer_is_sprint;
                    $forls['answer_text'] = strip_tags(html_entity_decode($v->answer, ENT_COMPAT | ENT_HTML401, 'UTF-8'));
                    //$forls['correct'] = $correct_answ;

                    if ($correct_answ == 1) {
                        $firstNumberforHash .= $answer_is_sprint;
                    }

                    $answerHash = $firstNumberforHash . $hash_salt; //config('constants.hash_salt');


                    array_push($ls, $forls);
                }

                if (!$firstNumberforHash) {
                    return response()->json([
                        'message' => 'No firstNumberforHash fix the dataBase -> must have one ore more correct answer : '.$answer_is_sprint_int,
                        'status' => false
                    ]);
                    die;
                }

                $qarr['answers'] = $ls;
            }
            //$qarr['answersHashORG'] = "hash('sha512', $answerHash)"; // https://www.tools4noobs.com/online_php_functions/sha512/
            $qarr['answersHash'] = hash('sha512', $answerHash);
            //$qarr['answersHashBase64encode'] = base64_encode(hash('sha512', $answerHash));
            array_push($tren, $qarr);
        }

        $response["examQuestions"] = $tren;

        return $response;

    }

    protected function showOne($data, $hash_salt, $successpercent)
    {

        $onev = explode("|", $data[0]);
        $idv = $onev[0];
        $idvcsv = $onev[0];

        $queryExams = DB::select("CALL selectOneExamSet($idv)");
        //$queryExams = DB::table('sqms_exam_version')->where('sqms_exam_version_id', $idv)->get();

        $idset = '';
        $i = 0;

        foreach ($queryExams as $k => $v) {
            $examNamefull = $v->sqms_exam_version_name;
            $sqms_exam_version_id = sprintf("%010d", $v->sqms_exam_version_id);
            $sqms_exam_set = sprintf("%05d", $v->sqms_exam_set);
            $sqms_exam_version = sprintf("%05d", $v->sqms_exam_version);
            $sqms_exam_version_sample_set = $v->sqms_exam_version_sample_set;
            $idset = $sqms_exam_version_id . '-' . $sqms_exam_set . '-' . $sqms_exam_version . '-' . $sqms_exam_version_sample_set . '-';
            $i++;
        }

        return $this->showAdv($idvcsv, $examNamefull, $idset, $hash_salt, $v->sqms_exam_set, $v->sqms_exam_version, $sqms_exam_version_sample_set, $successpercent);

    }

    protected function generateXML($data, $hash_salt)
    {

        $idvcsv = '';
        foreach ($data as $k => $v) {
            $onev = explode("|", $v);
            $idv[] = $onev[0];
            $idvcsv .= $onev[0] . ',';
        }

        $queryExams = DB::table(self::$tableName)->where('sqms_exam_version_id', $idv)->get();
        //$queryExams = DB::table('sqms_exam_version')->where('sqms_exam_version_id', $idv)->get();


        $domtree = new \DOMDocument('1.0', 'UTF-8');
        $xmlRoot = $domtree->createElement("quiz");
        $xmlRoot = $domtree->appendChild($xmlRoot);

        $idset = '';
        $i = 0;
        foreach ($queryExams as $k => $v) {
            $sqms_exam_version_id = sprintf("%010d", $v->sqms_exam_version_id);
            $sqms_exam_set = sprintf("%05d", $v->sqms_exam_set);
            $sqms_exam_version = sprintf("%05d", $v->sqms_exam_version);
            $sqms_exam_version_sample_set = $v->sqms_exam_version_sample_set;
            $idset = $sqms_exam_version_id . '-' . $sqms_exam_set . '-' . $sqms_exam_version . '-' . $sqms_exam_version_sample_set . '-';

            $xmlRoot->appendChild($domtree->createElement('examName', $v->sqms_exam_version_name));
            $xmlRoot->appendChild($domtree->createElement('set', $v->sqms_exam_set));
            $xmlRoot->appendChild($domtree->createElement('version', $v->sqms_exam_version));
            $xmlRoot->appendChild($domtree->createElement('SampleSet', $v->sqms_exam_version_sample_set));
            $xmlRoot->appendChild($domtree->createElement('id', rtrim($idset, "-")));

            $numberOfQuestionTotal = $this->numberOfQuestionTotal($idvcsv);

            $xmlRoot->appendChild($domtree->createElement('questionTotal', $numberOfQuestionTotal[0]->questionTotal));

            $i++;
        }

        // ADD COMMENT IN QUIZ
        $commentlink = "\n \"examName \" : " . "\"" . $v->sqms_exam_version_name . "\" \n";
        $commentlink .= "\"set \" : " . $v->sqms_exam_set . " \n";
        $commentlink .= "\"version \" : " . $v->sqms_exam_version . " \n";
        $commentlink .= "\"SampleSet \" : " . $v->sqms_exam_version_sample_set . " \n";
        $commentlink .= "\"id \" : " . "\"" . rtrim($idset, "-") . "\" \n";
        $commentlink .= "\"questionTotal \" : " . $numberOfQuestionTotal[0]->questionTotal . " \n";

        $comment = $domtree->createComment($commentlink);
        $xmlRoot->appendChild($comment);


        $numberOfQuestionTotalExam = DB::select("CALL examquestions('" . rtrim($idvcsv, ", ") . "')");
        foreach ($numberOfQuestionTotalExam as $k => $v) {


            $sprint_sqms_question_id = sprintf("%010d", $v->sqms_question_id);
            $qarr['question_id'] = $sprint_sqms_question_id;
            $qarr['question_text'] = $v->question;
            $qarr['answers'] = [];
            $qarr['answersSelected'] = [];

            $question_question = $v->question;

            $answer_is_sprint = '';
            $listanswers = DB::select("CALL listanswers($v->sqms_exam_version_id,$v->sqms_question_id)");
            if (count($listanswers) > 0) {
                foreach ($listanswers as $k => $v) {
                    $answer_is_sprint .= '-' . sprintf("%010d", $v->sqms_answer_id);
                }
            }

            // QUESTION
            $currentTrack = $domtree->createElement("question");
            $currentTrack = $xmlRoot->appendChild($currentTrack);
            $type = $domtree->createAttribute("type");
            $currentTrack->appendChild($type);
            $multichoiseset = $domtree->createTextNode("multichoiceset");
            $type->appendChild($multichoiseset);

            // NAME
            $name = $currentTrack->appendChild($domtree->createElement('name'));
            $text = $domtree->createElement("text");
            $name->appendChild($text);
            $text->appendChild($domtree->createCDATASection($sprint_sqms_question_id . $answer_is_sprint));

            // QUESTIONTEXT
            $questiontext = $currentTrack->appendChild($domtree->createElement('questiontext'));
            $format = $domtree->createAttribute("format");
            $questiontext->appendChild($format);
            $html = $domtree->createTextNode("html");
            $format->appendChild($html);

            $text = $questiontext->appendChild($domtree->createElement("text"));
            $text->appendChild($domtree->createCDATASection($question_question));

            // ANSWER
            if (count($listanswers) > 0) {
                foreach ($listanswers as $k => $v) {

                    $answer = $currentTrack->appendChild($domtree->createElement('answer'));
                    $fraction = $domtree->createAttribute("fraction");
                    $answer->appendChild($fraction);
                    $numberfraction = $domtree->createTextNode("100.00000");
                    $fraction->appendChild($numberfraction);

                    $text = $answer->appendChild($domtree->createElement("text"));
                    $text->appendChild($domtree->createCDATASection($v->answer));

                }
            }
        }

        $currentTrack->appendChild($domtree->createElement('shuffleanswers', 1));
        $currentTrack->appendChild($domtree->createElement('single', false));
        $currentTrack->appendChild($domtree->createElement('answernumbering', 'abc'));

        return $domtree->saveXML();
    }
}
