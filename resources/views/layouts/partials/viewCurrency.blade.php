<div class="row viewOddRow">
  <div class="col-md-3 col-sm-3 viewTD viewLeftTD">ID :</div>
  <div class="col-md-8 col-sm-8 col-md-offset-1 viewTD">{{ $currencyData["Id"] }}</div>
</div>
<div class="row">
  <div class="col-md-3 col-sm-3 viewTD viewLeftTD">Name :</div>
  <div class="col-md-8 col-sm-8 col-md-offset-1 viewTD">{{ $currencyData["Name"] }}</div>
</div>
<div class="row viewOddRow">
  <div class="col-md-3 col-sm-3 viewTD viewLeftTD">Code :</div>
  <div class="col-md-8 col-sm-8 col-md-offset-1 viewTD">{{ $currencyData["Code"] }}</div>
</div>
<div class="row">
  <div class="col-md-3 col-sm-3 viewTD viewLeftTD">Number :</div>
  <div class="col-md-8 col-sm-8 col-md-offset-1 viewTD">{{ $currencyData["Number"] }}</div>
</div>
<div class="row viewOddRow">
  <div class="col-md-3 col-sm-3 viewTD viewLeftTD">DigitsAfterDecimal :</div>
  <div class="col-md-8 col-sm-8 col-md-offset-1 viewTD">{{ $currencyData["DigitsAfterDecimal"] ?? "NULL" }}</div>
</div>
