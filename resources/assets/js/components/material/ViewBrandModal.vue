<template>
<div class="modal fade" tabindex="-1" role="dialog" id="ViewBrandModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-text-transform">View Brand</h4>
            </div>
            <div class="modal-body">
                <table class="table table-striped table-bordered">
                    <tbody>
                        <tr></tr>
                        <tr>
                            <td width="40%">#</td>
                            <td width="60%">{{ brandIndex }}</td>
                        </tr>
                        <tr>
                            <td>Name</td>
                            <td>{{ brandData.Name }}</td>
                        </tr>
                        <tr>
                            <td>Description</td>
                            <td v-html="getDescription(brandData.Description)"></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <span class="label label-success" v-if="brandData.IsActive">Active</span>
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
            "brand-data": {
                type: Object
            },
            "brand-index": {
                type: Number
            }
        },
        data() {
            return {
                categories: []
            };
        },
        created() {
            this.categories = this.$root.categories;
        },
        methods: {
            getCategory(categoryId) {
                if (categoryId) {
                    if (this.categories.length > 0) {
                        let catagory = _.find(this.categories, ["Id", categoryId]);
                        if (!_.isUndefined(catagory)) {
                            return catagory.Name;
                        }
                    }
                }
                return '<small>N/A</small>';
            },
            getDescription(description) {
                return (description) ? description : '<small>N/A</small>';
            },
            getWarranty(warranty) {
                if(warranty) {
                    return warranty + ' days';
                }
                return '<small>N/A</small>';
            }
        }
    }
</script>