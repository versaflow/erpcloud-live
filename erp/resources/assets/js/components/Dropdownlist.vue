<template>
  <div id="app">
    <h1>Hello Kendo UI for Vue!</h1>
    <p>
      <dropdownlist
        :data-items="categories"
        :data-item-key="'CategoryID'"
        :text-field="'CategoryName'"
        :default-item="defaultItems"
        @change="handleDropDownChange"
        >
      </dropdownlist>
      &nbsp; Selected category ID: <strong>{{this.dropdownlistCategory}}</strong>
    </p>
  </div>
</template>

<script>
import categories from './appdata/categories.json';
import { DropDownList } from '@progress/kendo-vue-dropdowns';
import '@progress/kendo-theme-default/dist/all.css';

export default {
  name: 'App',
  components: {
    'dropdownlist': DropDownList,
  },
  data: function() {
    return {
      categories: categories,
      defaultItems: {CategoryID: null, CategoryName: "Product categories"},
      dropdownlistCategory: null
     }
  },
  methods: {
      handleDropDownChange (e) {
          this.dropdownlistCategory = e.target.value.CategoryID;
      }
  }
}
</script>