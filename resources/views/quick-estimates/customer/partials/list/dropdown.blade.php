<li>
    <a href="{{ $estimate->getShowEstimateRoute() }}">
        <i class="fa fa-eye" aria-hidden="true"></i>
        <span>View Estimation</span>
    </a>
</li>
<li>
    <a href="{{ $estimate->getDownloadEstimateRoute("PDF") }}">
        <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
        <span>Download PDF</span>
    </a>
</li>
<li>
    <a href="{{ $estimate->getCopyEstimateRoute() }}">
        <i class="fa fa-clone" aria-hidden="true"></i>
        <span>Copy as New</span>
    </a>
</li>
