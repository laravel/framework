@extends('layouts/Pdfs/PDFTemplate')
@section('content')
<div class="box box-primary">
    <div class="box-header text-center pd-5">
        <h4>Site Measurement Pre-requisite</h4>
    </div>
    <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered SiteMeasurementCheclist">
                    <tbody>
                        <tr>
                            <td colspan="6" class="bg-info text-center">
                                <b >Before Site Visit</b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Customer Name</td>
                            <td colspan="3" width="50%">{{$UserFormData["CustomerInfo"]["Name"]}}</td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Customer Contact Number</td>
                            <td colspan="3" width="50%">{{$UserFormData["CustomerInfo"]["Phone"]}}</td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Project Name</td>
                            <td colspan="3" width="50%">{{$UserFormData["ProjectName"]}}</td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Site Address (Block No, Flat No, Landmark etc)</td>
                            <td colspan="3" width="50%"><?= $UserFormData["Address"] ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Unit Size</td>
                            <td colspan="3" width="50%">{{$UserFormData["SuperBuiltUpArea"]}} Sq ft</td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Contact Person Name at Site</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Mobile No of the contact person at Site</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Keys with whom</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">What is the process to get the keys</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Inform the customer before going to the Site</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                         <tr>
                            <td colspan="6" class="bg-info text-center">
                                <b>During Site Visit</b>
                                <p><span class="text-danger">*</span> Make sure that the engineer / supervisor who’s going for site visit has Measuring tape, Laser rangefinder, Compass, Pencil, Eraser, Plain;
                                    notepad and all the details related to the site.</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Take detail measurement of the site using Site measurement template</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Take photos of the site</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Take videos of the site</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                        <tr>
                            <td colspan="6" class="bg-info text-center">
                                <b>After Site Visit</b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Upload the photos and videos into a common folder</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Inform the customer post site measurement</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Keys Retained / Returned</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                         <tr>
                            <td colspan="6" class="bg-info text-center">
                                <b>Internal Use</b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Site Visit Date and Time</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Site Visit Done By (Supervisor / Engineer Name)</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                        <tr>
                            <td colspan="3" width="50%">Reviewed By</td>
                            <td colspan="3" width="50%"></td>
                        </tr>
                        <tr>
                            <td colspan="6" style="height: 90px"><b>Special Notes:</b></td>
                        </tr>
                </tbody>
                    </table>
                </div>
                <div class="col-md-12" style="padding: 150px!important">&nbsp;&nbsp;</div>
            </div>
        </div>
</div>
@endsection