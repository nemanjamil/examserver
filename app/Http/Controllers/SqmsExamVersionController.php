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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }


    protected function  showMore($data){
        $idvcsv = '';
        foreach ($data as $k => $v) {
            $onev = explode("|",$v);
            $idv[] = $onev[0];
            $idvcsv .= $onev[0].',';
        }

        $queryExams = DB::table('sqms_exam_version')->whereIn('sqms_exam_version_id', $idv)->get();
        $examNamefull = "COMBI ";
        $idset = '';
        $i = 0;

        foreach($queryExams as $k => $v) {
            $v->sqms_exam_version_id;
            $examNamefull .= $v->sqms_exam_version_name."($i), ";

            $sqms_exam_version_id = sprintf("%010d", $v->sqms_exam_version_id);
            $sqms_exam_set = sprintf("%05d", $v->sqms_exam_set);
            $sqms_exam_version = sprintf("%05d", $v->sqms_exam_version);
            $sqms_exam_version_sample_set = $v->sqms_exam_version_sample_set;
            $idset .= $sqms_exam_version_id.'-'.$sqms_exam_set.'-'.$sqms_exam_version.'-'.$sqms_exam_version_sample_set.'-';
            $i++;
        }

        $numberOfQuestionTotal =  DB::select("CALL countexams('".rtrim($idvcsv,", ")."')");

        $response["examName"] = rtrim($examNamefull,", ");
        $response["set"] = 2;
        $response["version"] = 1;
        $response["SampleSet"] = false;
        $response["id"] = rtrim($idset,"-");
        $response["uid"] = 0;
        $response["time"] = 0;
        $response["starttime"] = 0;
        $response["duration"] = 0;
        $response["questionIndex"] = 0;
        $response["questionTotal"] = $numberOfQuestionTotal[0]->questionTotal;
        $response["firstname"] = "";
        $response["lastname"] = "";
        $response["answerOptions"] = [];
        $response["examQuestions"] = [];




        return  response()->json($response);

    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Sqms_exam_version  $sqms_exam_version
     * @return \Illuminate\Http\Response
     */
    public function show(Sqms_exam_version $sqms_exam_version,Request $request)
    {
        $data = $request->all()["data"];

        if (count($data)>1){

            return $this->showMore($data);

        } else {

        }

        //$row = DB::table('device')->whereIn('id',$man)->pluck('id');
        //return Sqms_exam_version::all();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Sqms_exam_version  $sqms_exam_version
     * @return \Illuminate\Http\Response
     */
    public function edit(Sqms_exam_version $sqms_exam_version)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Sqms_exam_version  $sqms_exam_version
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sqms_exam_version $sqms_exam_version)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Sqms_exam_version  $sqms_exam_version
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sqms_exam_version $sqms_exam_version)
    {
        //
    }
}
