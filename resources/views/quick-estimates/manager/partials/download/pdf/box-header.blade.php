<div class="box-header with-border">
    <div class="row">
        <div class="col-md-12">
            <table class="mr-bt-15">
                <thead>
                    <tr>
                        <th width="20%" class="pd-10 pd-tp-0">Reference Number</th>
                        <th width="30%" class="pd-10 pd-tp-0">Name</th>
                        <th width="50%" class="pd-10 pd-tp-0">Enquiry</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td width="20%" class="pd-10">{{ $estimate->referenceNumber }}</td>
                        <td width="30%" class="pd-10">{{ $estimate->name }}</td>
                        <td width="50%" class="pd-10">{{ $enquiry->referenceNumber }} ({{ $enquiry->name }})</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
