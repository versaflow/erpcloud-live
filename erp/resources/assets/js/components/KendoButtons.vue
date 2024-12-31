<template>
    <div class="row example-wrapper">
        <div class="col-xs-12 col-sm-6 example-col">
            <p>Default k-buttons</p>
            <p>
                <k-button >Browse</k-button>
                <k-button :icon="'folder'">Browse</k-button>
                <k-button :icon="'folder'"></k-button>
            </p>
            <p>
                <k-button :disabled="true">Browse</k-button>
                <k-button :icon="'folder'" :disabled="true">Browse</k-button>
                <k-button :icon="'folder'" :disabled="true"></k-button>
            </p>
        </div>

        <div class="col-xs-12 col-sm-6 example-col">
            <p>Default k-buttons (Primary)</p>
            <p>
                <k-button :primary="true">Browse</k-button>
                <k-button :icon="'folder'" :primary="true">Browse</k-button>
                <k-button :icon="'folder'" :primary="true"></k-button>
            </p>
            <p>
                <k-button :primary="true" :disabled="true">Browse</k-button>
                <k-button :icon="'folder'" :primary="true" :disabled="true">Browse</k-button>
                <k-button :icon="'folder'" :primary="true" :disabled="true"></k-button>
            </p>
        </div>

        <div class="col-xs-12 col-sm-6 example-col">
            <p>Flat k-buttons</p>
            <p>
                <k-button :look="'flat'">Browse</k-button>
                <k-button :icon="'folder'" :look="'flat'">Browse</k-button>
                <k-button :icon="'folder'" :look="'flat'"></k-button>
            </p>
            <p>
                <k-button :look="'flat'" :disabled="true">Browse</k-button>
                <k-button :icon="'folder'" :look="'flat'" :disabled="true">Browse</k-button>
                <k-button :icon="'folder'" :look="'flat'" :disabled="true"></k-button>
            </p>
        </div>

        <div class="col-xs-12 col-sm-6 example-col">
            <p>Flat k-buttons (Primary)</p>
            <p>
                <k-button :primary="true" :look="'flat'">Browse</k-button>
                <k-button :icon="'folder'" :primary="true" :look="'flat'">Browse</k-button>
                <k-button :icon="'folder'" :primary="true" :look="'flat'"></k-button>
            </p>
            <p>
                <k-button :primary="true" :look="'flat'" :disabled="true">Browse</k-button>
                <k-button :icon="'folder'" :primary="true" :look="'flat'" :disabled="true">Browse</k-button>
                <k-button :icon="'folder'" :primary="true" :look="'flat'" :disabled="true"></k-button>
            </p>
        </div>

        <div class="col-xs-12 col-sm-6 example-col">
            <p>Outline k-buttons</p>
            <p>
                <k-button :look="'outline'">Browse</k-button>
                <k-button :icon="'folder'" :look="'outline'">Browse</k-button>
                <k-button :icon="'folder'" :look="'outline'"></k-button>
            </p>
            <p>
                <k-button :look="'outline'" :disabled="true">Browse</k-button>
                <k-button :icon="'folder'" :look="'outline'" :disabled="true">Browse</k-button>
                <k-button :icon="'folder'" :look="'outline'" :disabled="true"></k-button>
            </p>
        </div>

        <div class="col-xs-12 col-sm-6 example-col">
            <p>Outline k-buttons (Primary)</p>
            <p>
                <k-button :primary="true" :look="'outline'">Browse</k-button>
                <k-button :icon="'folder'" :primary="true" :look="'outline'">Browse</k-button>
                <k-button :icon="'folder'" :primary="true" :look="'outline'"></k-button>
            </p>
            <p>
                <k-button :primary="true" :look="'outline'" :disabled="true">Browse</k-button>
                <k-button :icon="'folder'" :primary="true" :look="'outline'" :disabled="true">Browse</k-button>
                <k-button :icon="'folder'" :primary="true" :look="'outline'" :disabled="true"></k-button>
            </p>
        </div>

        <div class="col-xs-12 example-col">
            <p>Icon Types</p>
            <k-button :icon="'calendar'">Kendo UI for Vue Font Icon</k-button>
            <k-button :icon-class="'fa fa-calendar fa-fw'">Font Awesome Icon</k-button>
            <k-button :image-url="'https://demos.telerik.com/kendo-ui/content/shared/icons/sports/snowboarding.png'">Image Icon</k-button>
        </div>
    </div>
</template>
<script>
import { Button, ButtonGroup } from '@progress/kendo-vue-buttons';

export default {
    components: {
        'k-button': Button,
        'buttongroup': ButtonGroup
    }
}
</script>
<style scoped>
  .k-button{
    margin-right: 3px;
  }

  .k-button > .k-image{
    width: 20px;
    height: 20px;
  }
</style>