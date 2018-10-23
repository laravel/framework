@extends("layouts/Pdfs/PDFTemplate")

@section("content")
<div class="box box-primary">
    <div class="box-body pd-0">
        @include("quick-estimates.customer.partials.email.pdf.specifications")
        @include("quick-estimates.customer.partials.email.pdf.pdfQuickEstStatistics")
        @include("quick-estimates.customer.partials.email.pdf.table")
        @include("quick-estimates.customer.partials.email.pdf.notes")
        @include("quick-estimates.customer.partials.email.pdf.RoomwiseStatistics")
    </div>
</div>
@endsection
