<?php

namespace App\Http\Controllers;

use App\Azure;
use Illuminate\Http\Request;
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use Illuminate\Support\Facades\Storage;





class AzureController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $connectionString = "DefaultEndpointsProtocol=http;AccountName=".env('AZURE_ACCOUNT_NAME').";AccountKey=".env('AZURE_ACCOUNT_KEY');
        $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($connectionString);

        $content = Storage::get('public/000005468600002000010/000005468600002000010.json');
        $blob_name = "testbloblaravel.json";

        try {
            $options = new CreateBlobOptions();
            $contentType = 'application/json';
            $options->setContentType($contentType);
            $blobRestProxy->createBlockBlob(env('AZURE_CONTAINER'), $blob_name, $content,$options);
        } catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }

        echo $connectionString;
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
     * @param  \App\Azure  $azure
     * @return \Illuminate\Http\Response
     */
    public function show(Azure $azure)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Azure  $azure
     * @return \Illuminate\Http\Response
     */
    public function edit(Azure $azure)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Azure  $azure
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Azure $azure)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Azure  $azure
     * @return \Illuminate\Http\Response
     */
    public function destroy(Azure $azure)
    {
        //
    }
}
