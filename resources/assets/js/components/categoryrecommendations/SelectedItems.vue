<template>
    <div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered no-footer" id="SelectedItemsTable">
                <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue">
                    <tr>
                        <th class="rate-text-center sno pd-10" width="4%">#</th>
                        <th class="rate-text-center">{{ headers[0] }}</th>
                        <th class="rate-text-center">{{ headers[1] }}</th>
                        <th class="rate-text-center">{{ headers[2] }}</th>
                        <th class="rate-text-center">{{ headers[3] }}</th>
                        <th class="rate-text-center">{{ headers[4] }}</th>
                        <th class="pd-10 rate-text-center" width="12%">{{ headers[5] }}</th>
                    </tr>
                </thead>
               <tbody>
                   <tr v-for="(item, index) in selectedItems">
                       <td class="rate-text-center">{{ index+ 1 }}</td> 
                       <td class="text-vertical-align">{{ item.Brand }}</td>
                       <td class="text-vertical-align">{{ item.SubBrand }}</td>
                       <td class="text-vertical-align">{{ item.Name }}</td>
                       <td class="text-vertical-align" v-html="item.Number"></td>
                       <td class="text-vertical-align">{{ getStatus(item.SelectedBy, item.ShortlistedBy, item.Status) }}</td>
                       <td class="rate-text-center">
                            <a :href="viewItemRoute+'/'+categorySlug+'/'+item.MaterialId"
                              data-toggle="tooltip" 
                              title="" 
                              class="cursor-pointer quick-view" 
                              data-original-title="Quick View"
                              :data-material-id="item.MaterialId"
                              >
                              <i class="fa fa-fw fa-eye"></i>
                           </a>
                            <a :href="viewItemRoute+'/'+categorySlug+'/'+item.MaterialId"
                              data-toggle="tooltip" 
                              title="" 
                              class="cursor-pointer view-material" 
                              data-original-title="Detailed View"
                              target="_blank"
                              >
                              <i class="fa fa-cart-plus"></i>
                           </a>
                            <a href="javascript:void(0)"
                              data-toggle="tooltip" 
                              title="" 
                              class="cursor-pointer update-material" 
                              :data-original-title="isCustomer ? 'Shortlist Material': 'Finalize Material'"
                              :data-collection-id="item.CollectionId"
                              :data-api-end-point="updateItemStatusRoute"
                              v-if="item.Status === '0'"
                              >
                              <i class="fa fa-fw fa-check-circle"></i>
                            </a>
                            <a href="javascript:void(0)" 
                              data-toggle="tooltip" 
                              title="" 
                              class="cursor-pointer delete-material" 
                              data-original-title="Delete Material"
                              :data-collection-id="item.CollectionId"
                              :data-projectmaterial-id="item.ProjectMaterialId"
                              :data-api-end-point="deleteItemRoute"
                              v-if="canDelete(item.SelectedBy, item.Status)"
                              >
                              <i class="fa fa-fw fa-trash"></i>
                           </a>
                       </td>
                   </tr>
               </tbody>
            </table>
        </div>
    </div>
</template>

<script>
    // Child component
    export default {
        props: {
            "headers": {
                type: Array
            },
            "selected-items": {
                type: Array
            }
        },
        data() {
            return {
                viewItemRoute: null,
                updateItemStatusRoute: null,
                deleteItemRoute: null,
                isCustomer: false,
                categorySlug: null
            }
        },
        created() {
            this.viewItemRoute = this.$root.viewItemRoute;
            this.updateItemStatusRoute = this.$root.updateItemStatusRoute;
            this.deleteItemRoute = this.$root.deleteItemRoute;
            this.types = this.$root.types;
            this.isCustomer = this.$root.isCustomer;
            this.categorySlug = this.$root.categorySlug;
        },
        methods: {
            getStatus(selectedBy, shortBy, status) {
                if(status === '0') {
                    if(selectedBy === "Customer") {
                        return 'Shortlisted By Customer';
                    }
                    return 'Recommended By Designer';
                }
                if(status === '1') {
                    if(shortBy === "Customer") {
                        return 'Finalized By Customer';
                    }
                    return 'Finalized By Designer';
                }
            },
            canDelete(selectedBy, status) {
                if(status === '0') {
                    if(selectedBy === "Customer") {
                        return true;
                    } 
                    if(selectedBy === "Designer" && this.isCustomer == false) {
                        return true;
                    }
                    return false;
                }
                if(status === '1') {
                   if(this.isCustomer == true) {
                        return false;
                   }
                   return true;
                }
            }
        }   
    }
</script>