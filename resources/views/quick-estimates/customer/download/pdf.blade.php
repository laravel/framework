@extends("layouts/Pdfs/PDFTemplate")

@section("content")
    <div class="row mr-0">
        <div class="col-md-12">
            <div class="box box-primary">
                @include("quick-estimates.manager.partials.download.pdf.box-header")
                <div class="box-body pd-0">
                    @include("quick-estimates.manager.partials.download.pdf.table")
                    @include("quick-estimates.manager.partials.download.pdf.notes")
                </div>
            </div>
        </div>
    </div>
@endsection
