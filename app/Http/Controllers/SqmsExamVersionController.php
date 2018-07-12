<?php

namespace App\Http\Controllers;

use App\Sqms_exam_version;
use Illuminate\Http\Request;

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

    /**
     * Display the specified resource.
     *
     * @param  \App\Sqms_exam_version  $sqms_exam_version
     * @return \Illuminate\Http\Response
     */
    public function show(Sqms_exam_version $sqms_exam_version)
    {
        //
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
