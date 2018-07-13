<?php

namespace App\Http\Controllers;

use App\Sqms_exam_version;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SqmsExamVersionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        return Sqms_exam_version::all();
    }

    public function hashsalt(){
        return response()->json(config('constants.hash_salt'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }


    protected function  showMore($data)
    {
        $idvcsv = '';
        foreach ($data as $k => $v) {
            $onev = explode("|", $v);
            $idv[] = $onev[0];
            $idvcsv .= $onev[0] . ',';
        }

        $queryExams = DB::table('sqms_exam_version')->whereIn('sqms_exam_version_id', $idv)->get();

        $examNamefull = "COMBI ";
        $idset = '';
        $i = 0;

        foreach ($queryExams as $k => $v) {
            $v->sqms_exam_version_id;
            $examNamefull .= $v->sqms_exam_version_name . "($i), ";

            $sqms_exam_version_id = sprintf("%010d", $v->sqms_exam_version_id);
            $sqms_exam_set = sprintf("%05d", $v->sqms_exam_set);
            $sqms_exam_version = sprintf("%05d", $v->sqms_exam_version);
            $sqms_exam_version_sample_set = $v->sqms_exam_version_sample_set;
            $idset .= $sqms_exam_version_id . '-' . $sqms_exam_set . '-' . $sqms_exam_version . '-' . $sqms_exam_version_sample_set . '-';
            $i++;
        }

        return $this->showAdv($idvcsv,$examNamefull,$idset);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Sqms_exam_version $sqms_exam_version
     * @return \Illuminate\Http\Response
     */
    public function show(Sqms_exam_version $sqms_exam_version, Request $request)
    {
        $datafull = $request->all();
        $data = $datafull["data"];
        $hash_salt = $datafull["hash_salt"];

        if (!$hash_salt) {
            return response()->json([
                'hash_salt' => 'No hash_salt, please add one',
                'status' => 'fail'
            ]);
            die;
        }
        if (count($data) > 1) {
            return $this->showMore($data);
        } else {
            return $this->showOne($data);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Sqms_exam_version $sqms_exam_version
     * @return \Illuminate\Http\Response
     */
    public function edit(Sqms_exam_version $sqms_exam_version)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Sqms_exam_version $sqms_exam_version
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sqms_exam_version $sqms_exam_version)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Sqms_exam_version $sqms_exam_version
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sqms_exam_version $sqms_exam_version)
    {
        //
    }

    protected function showAdv($idvcsv,$examNamefull,$idset){

        $numberOfQuestionTotal = DB::select("CALL countexams('" . rtrim($idvcsv, ", ") . "')");

        $response["examName"] = rtrim($examNamefull, ", ");
        $response["set"] = 2;
        $response["version"] = 1;
        $response["SampleSet"] = false;
        $response["id"] = rtrim($idset, "-");
        $response["uid"] = 0;
        $response["time"] = 0;
        $response["starttime"] = 0;
        $response["duration"] = 0;
        $response["questionIndex"] = 0;
        $response["questionTotal"] = $numberOfQuestionTotal[0]->questionTotal;
        $response["firstname"] = "";
        $response["lastname"] = "";
        $response["answerOptions"] = [];


        $numberOfQuestionTotal = DB::select("CALL examquestions('" . rtrim($idvcsv, ", ") . "')");
        $tren = [];
        foreach ($numberOfQuestionTotal as $k => $v) {
            $sprint_sqms_question_id = sprintf("%010d", $v->sqms_question_id);
            $qarr['question_id'] = $sprint_sqms_question_id;
            $qarr['question_text'] = $v->question;
            //$qarr['sqms_exam_version_id'] = $v->sqms_exam_version_id;
            $qarr['answers'] = [];
            $qarr['answersSelected'] = [];


            $numberOfQuestionTotal = DB::select("CALL examquestions('" . rtrim($idvcsv, ", ") . "')");

            $ls = [];
            $listanswers = DB::select("CALL listanswers($v->sqms_exam_version_id,$v->sqms_question_id)");
            if (count($listanswers) > 0) {
                $varHashFirstNumber = true;
                foreach ($listanswers as $k => $v) {


                    $answer_is_sprint = sprintf("%010d", $v->sqms_answer_id);
                    $forls['answer_id'] = $answer_is_sprint;
                    $forls['answer_text'] = $v->answer;
                    $forls['correct'] = $v->correct;
                    if ($v->correct == 1) {
                        if ($varHashFirstNumber){
                            $firstNumberforHash = $answer_is_sprint;
                            $varHashFirstNumber = false;
                        }
                    }

                    if (!$firstNumberforHash) {
                        return response()->json([
                            'firstNumberforHash' => 'No firstNumberforHash',
                            'status' => 'fail'
                        ]);
                        die;
                    }
                    $answerHash = $firstNumberforHash.$answer_is_sprint.config('constants.hash_salt');


                    array_push($ls, $forls);
                }
                $qarr['answers'] = $ls;
            }
            $qarr['answersHashORG'] = "hash('sha512', $answerHash)"; // https://www.tools4noobs.com/online_php_functions/sha512/
            $qarr['answersHash'] = hash('sha512', $answerHash);
            $qarr['answersHashBase64encode'] =  base64_encode(hash('sha512', $answerHash));
            array_push($tren, $qarr);
        }


        $response["examQuestions"] = $tren;


        return response()->json($response);


    }
    protected function  showOne($data)
    {

        $onev = explode("|", $data[0]);
        $idv = $onev[0];
        $idvcsv = $onev[0];

        $queryExams = DB::table('sqms_exam_version')->where('sqms_exam_version_id', $idv)->get();

        $idset = '';
        $i = 0;

        foreach ($queryExams as $k => $v) {
            $v->sqms_exam_version_id;
            $examNamefull = $v->sqms_exam_version_name;
            $sqms_exam_version_id = sprintf("%010d", $v->sqms_exam_version_id);
            $sqms_exam_set = sprintf("%05d", $v->sqms_exam_set);
            $sqms_exam_version = sprintf("%05d", $v->sqms_exam_version);
            $sqms_exam_version_sample_set = $v->sqms_exam_version_sample_set;
            $idset = $sqms_exam_version_id . '-' . $sqms_exam_set . '-' . $sqms_exam_version . '-' . $sqms_exam_version_sample_set . '-';
            $i++;
        }

        return $this->showAdv($idvcsv,$examNamefull,$idset);

    }
}
