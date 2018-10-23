<div class="box-body mr-tp-10 table-responsive" id="MatSearchResultsBody">
    @if(count($materials) > 0)
    <table class="table table-striped table-bordered dataTable no-footer" id="MaterialsListTable">
        <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
            <tr>
                <th class="text-center sno pd-10">#</th>
                <th>UserName</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Sub Brand</th>
                <th>Created On</th>
                <th>Updated On</th>
                <th class="pd-10"></th>
            </tr>
        </thead>
        @foreach($materials as $key => $material)
        <tr>
            <td class="text-center text-vertical-align">{{ $key + 1 }}</td>
            <td class="text-vertical-align">{{ $material["UserName"] }}</td>
            <td class="text-vertical-align">{{ $material["Category"] }}</td>
            <td class="text-vertical-align">{{ $material["Brand"] }}</td>
            <td class="text-vertical-align">{{ $material["SubBrand"] }}</td>
            <td class="text-vertical-align">{{ $material["CreatedAt"] }}</td>
            <td class="text-vertical-align">{{ $material["UpdatedAt"] }}</td>
            <td class="text-vertical-align text-center">
                <a href="{{ route($material['Slug'], ['id' => $material['editKey']]) }}" class="cursor-pointer" id="Edit" data-toggle="tooltip" title="Edit">
                    <i class="fa fa-fw fa-pencil"></i>
                </a>
                <a href="{{ route('materials.view',['slug' => $material['Slug'], 'id' => $material['editKey']]) }}" class="cursor-pointer" id="Edit" data-toggle="tooltip" title="View">
                    <i class="fa fa-fw fa-eye"></i>
                </a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @else 
    <div class="callout callout-info mr-tp-15 mr-bt-15">No results found for the given search parameters.</div>
    @endif
</div>