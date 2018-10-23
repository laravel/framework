@extends("layouts/Pdfs/PDFTemplate")

@section("content")
<div class="box box-primary">
    <div class="box-body pd-0">
        @include("quick-estimates.manager.partials.email.pdf.specifications")
        @include("quick-estimates.manager.partials.email.pdf.pdfQuickEstStatistics")
        @include("quick-estimates.manager.partials.email.pdf.table")
        @include("quick-estimates.manager.partials.email.pdf.notes")
        @include("quick-estimates.manager.partials.email.pdf.RoomwiseStatistics")
    </div>
</div>
@endsection
