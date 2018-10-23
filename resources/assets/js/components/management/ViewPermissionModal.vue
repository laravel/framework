<template>
<div class="modal fade" tabindex="-1" role="dialog" id="ViewPermissionModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title no-text-transform">View Permission</h4>
            </div>
            <div class="modal-body">
                <table class="table table-striped table-bordered">
                    <tbody>
                        <tr></tr>
                        <tr>
                            <td width="40%">#</td>
                            <td width="60%">{{ permissionIndex }}</td>
                        </tr>
                        <tr>
                            <td>Title</td>
                            <td>{{ permissionData.Title }}</td>
                        </tr>
                        <tr>
                            <td>Slug</td>
                            <td>{{ permissionData.Slug }}</td>
                        </tr>
                        <tr>
                            <td>Description</td>
                            <td v-html="getDescription(permissionData.Description)"></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <span class="label label-success" v-if="permissionData.IsActive">Active</span>
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
            "permission-data": {
                type: Object
            },
            "permission-index": {
                type: Number
            }
        },
        data() {
            return {
                categories: []
            };
        },
        methods: {
            getDescription(description) {
                return (description) ? description : '<small>N/A</small>';
            }
        }
    }
</script>