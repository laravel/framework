<!-- Default box -->
<div class="box">
  <div class="box-header with-border">
    <h3 class="box-title"><span class="glyphicon glyphicon-pencil"></span> Edit record</h3>
  </div>
  <form class="" id="recordEditForm" method="post">
    <div class="box-body">
      <div class="container-fluid">
        <div class="row viewOddRow">
          <div class="col-md-3 col-sm-3 viewTD viewLeftTD">ID :</div>
          <div class="col-md-8 col-sm-8 col-md-offset-1 viewTD">
            <input type="text" name="Id" value="{{ $currencyData["Id"] }}" class="form-control" readonly="readonly"/>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-3 viewTD viewLeftTD">Name :</div>
          <div class="col-md-8 col-sm-8 col-md-offset-1 viewTD">
            <div class="form-group" style="margin-bottom:0px !important">
              <input type="text" name="name" value="{{ $currencyData["Name"] }}" class="form-control checkOrig" placeholder="Name" aria-describedby="nameHelpBlock"/>
              <span id="nameHelpBlock" class="help-block hidden" style="margin-bottom:0px !important">This field should not be empty.</span>
            </div>
          </div>
        </div>
        <div class="row viewOddRow">
          <div class="col-md-3 col-sm-3 viewTD viewLeftTD">Code :</div>
          <div class="col-md-8 col-sm-8 col-md-offset-1 viewTD">
            <div class="form-group" style="margin-bottom:0px !important">
              <input type="text" name="code" value="{{ $currencyData["Code"] }}" class="form-control checkOrig" placeholder="Code" aria-describedby="codeHelpBlock"/>
              <span id="codeHelpBlock" class="help-block hidden" style="margin-bottom:0px !important">This field should not be empty.</span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-3 viewTD viewLeftTD">Number :</div>
          <div class="col-md-8 col-sm-8 col-md-offset-1 viewTD">
            <div class="form-group" style="margin-bottom:0px !important">
              <input type="text" name="number" value="{{ $currencyData["Number"] }}" class="form-control checkOrig" placeholder="Number" aria-describedby="numberHelpBlock"/>
              <span id="numberHelpBlock" class="help-block hidden" style="margin-bottom:0px !important">This field should not be empty.</span>
            </div>
          </div>
        </div>
        <div class="row viewOddRow">
          <div class="col-md-3 col-sm-3 viewTD viewLeftTD">DigitsAfterDecimal :</div>
          <div class="col-md-8 col-sm-8 col-md-offset-1 viewTD">
            <input type="text" name="decimal" value="{{ $currencyData["DigitsAfterDecimal"] ?? "null" }}" class="form-control checkOrig" placeholder="DigitsAfterDecimal"/>
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
          </div>
        </div>
      </div>
    </div><!-- /.box-body -->
    <div class="box-footer">
      <button type="button" name="back" id="backBtn" class="btn btn-default"><span class="glyphicon glyphicon-arrow-left"></span> Back</button>&nbsp;&nbsp;<button type="submit" name="save" class="btn btn-primary"><span class="glyphicon glyphicon-ok"></span> Save changes</button>
    </div><!-- /.box-footer-->
  </form>
  <div class="alert hidden alert-dismissible" id="editFormAlert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <span><i class="icon"></i><strong id="alert-title"></strong></span>
    <span id="alert-data"></span>
  </div>
</div><!-- /.box -->

<script src="{{ URL::assetUrl('/js/editCurrency.js') }}"></script>
