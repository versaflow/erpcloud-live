<?php

function button_flowcharts_view_flowchart($request)
{
    $upload_url = uploads_url(683);
    $flowchart = \DB::table('crm_flowcharts')->where('id', $request->id)->get()->first();
    if (empty($flowchart->flowchart)) {
    }
    return redirect()->to($upload_url.$flowchart->flowchart);
}

function button_flowcharts_edit_flowchart($request)
{
    $flowchart = \DB::table('crm_flowcharts')->where('id', $request->id)->get()->first();
    $data = (array) $flowchart;
    $data['menu_name'] = ucwords($flowchart->name).' Flowchart';
    return view('__app.components.diagram', $data);
}
