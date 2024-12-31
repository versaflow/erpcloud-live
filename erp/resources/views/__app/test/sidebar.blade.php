@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))


@section('content')
<div class="row py-2 px-3"><h6> Processes</h6><div class="col-lg-2 module_card">
        <div class="card" id="module_card">
        <div class="card-header bg-primary text-white py-2"><div class="row p-0">
        <div class="col d-flex align-items-center">
        <span class="d-block mb-0 text-nowrap">Mohammed Kola</span>
        </div>
        </div></div><div class="card-body py-2"><div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 60%;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">6/10</div>
            </div></div></div>
        </div><div class="col-lg-2 module_card">
        <div class="card" id="module_card">
        <div class="card-header bg-primary text-white py-2"><div class="row p-0">
        <div class="col d-flex align-items-center">
        <span class="d-block mb-0 text-nowrap">Jibril Djuma</span>
        </div>
        </div></div><div class="card-body py-2"><div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 60%;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">6/10</div>
            </div></div></div>
        </div><div class="col-lg-2 module_card">
        <div class="card" id="module_card">
        <div class="card-header bg-primary text-white py-2"><div class="row p-0">
        <div class="col d-flex align-items-center">
        <span class="d-block mb-0 text-nowrap">Ahmed Nani</span>
        </div>
        </div></div><div class="card-body py-2"><div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0/5</div>
            </div></div></div>
        </div><div class="col-lg-2 module_card">
        <div class="card" id="module_card">
        <div class="card-header bg-primary text-white py-2"><div class="row p-0">
        <div class="col d-flex align-items-center">
        <span class="d-block mb-0 text-nowrap">Gustaf Landman</span>
        </div>
        </div></div><div class="card-body py-2"><div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 77%;" aria-valuenow="77" aria-valuemin="0" aria-valuemax="100">7/9</div>
            </div></div></div>
        </div><div class="col-lg-2 module_card">
        <div class="card" id="module_card">
        <div class="card-header bg-primary text-white py-2"><div class="row p-0">
        <div class="col d-flex align-items-center">
        <span class="d-block mb-0 text-nowrap">Javaid Alimia</span>
        </div>
        </div></div><div class="card-body py-2"><div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0/1</div>
            </div></div></div>
        </div></div>

@endsection

