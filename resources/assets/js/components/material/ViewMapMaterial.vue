<template>
    <div class="modal fade" tabindex="-1" role="dialog" id="ViewMapMaterialModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title no-text-transform">View</h4>
                </div>
                <div class="modal-body">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr>
                                <td>#</td>
                                <td>{{selectedMaterial.Index}}</td>
                            </tr>
                            <tr>
                                <td>Brand</td>
                                <td>{{getBrand(selectedMaterial.Brand)}}</td>
                            </tr>
                            <tr>
                                <td>SubBrand</td>
                                <td >{{getSubBrand(selectedMaterial.SubBrand)}}</td>
                            </tr>
                            <tr>
                                <td>FormCategory</td>
                                <td >{{getFormCategory(selectedMaterial.FormCategory)}}</td>
                            </tr>
                            <tr>
                                <td>Guarantee</td>
                                <td >{{selectedMaterial.Guarantee}}</td>
                            </tr>
                            <tr>
                                <td>Warranty</td>
                                <td>{{selectedMaterial.Warranty}}</td>
                            </tr>
                            <tr>
                                <td>Status</td>
                                <td>
                                    <span class="label label-success" v-if="selectedMaterial.IsActive==1">Active</span>
                                    <span class="label label-danger" v-else>InActive</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    // Child component
    export default {
        props: {
            "selected-material": {
                type: Object
            },
            "brands":{
                type: Array
            },
            "sub-brands":{
                type: Array
            },
            "form-categories":{
                type: Array
            }
        },
        methods: {
            getBrand(brandId) {
                if (brandId) {
                    if (this.brands.length > 0) {
                        let brand = _.find(this.brands, ["Id", brandId]);
                        if (!_.isUndefined(brand)) {
                            return brand.Name;
                        }
                    }
                }
                return '<small>N/A</small>';
            },
            getSubBrand(SubBrandId) {
                if (SubBrandId) {
                    if (this.subBrands.length > 0) {
                        let subBrand = _.find(this.subBrands, ["Id", SubBrandId]);
                        if (!_.isUndefined(subBrand)) {
                            return subBrand.Name;
                        }
                    }
                }
                return '<small>N/A</small>';
            },
            getFormCategory(FormCategoryId) {
                if (FormCategoryId) {
                    if (this.formCategories.length > 0) {
                        let formCategory = _.find(this.formCategories, ["Id", FormCategoryId]);
                        if (!_.isUndefined(formCategory)) {
                            return formCategory.Name;
                        }
                    }
                }
                return '<small>N/A</small>';
            }
        }
    }
</script>