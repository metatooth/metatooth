<script lang="ts">
import { PropType } from 'vue';
import ContactRow from './ContactRow.vue';
import { Contact } from '../../../lib/types';

export default {
  props: {
    data: { type: Array as PropType<Contact[]>, required: true },
    columns: { type: Array as PropType<string[]>, required: true },
    filterKey: { type: String, default: '' },
  },
  data() {
    const obj: Record<string, any> = {};
    return {
      sortKey: this.columns[0],
      sortOrders: this.columns.reduce((o, key) => ((o[key] = 1), o), obj),
    };
  },
  components: {
    ContactRow
  },
  computed: {
    filtered() {
      const { sortKey } = this;
      const filterKey = this.filterKey && this.filterKey.toLowerCase();
      const order = this.sortOrders[sortKey] || 1;
      let data = this.data || [];
      if (filterKey) {
        data = data.filter((row) => {
          return Object.keys(row).some((key) => {
            return String(row[key]).toLowerCase().indexOf(filterKey) > -1;
          });
        });
      }
      if (sortKey) {
        data = data.slice().sort((a: Record<string, any>, b: Record<string, any>) => {
          a = a[sortKey];
          b = b[sortKey];
          return (a === b ? 0 : a > b ? 1 : -1) * order;
        });
      }
      return data;
    },
  },
  methods: {
    capitalize(str: string) {
      return str.charAt(0).toUpperCase() + str.slice(1);
    },
    sortBy(key: string) {
      this.sortKey = key;
      this.sortOrders[key] = this.sortOrders[key] * -1;
    },
  },
};
</script>

<template>
  <div  class="table-container" v-if="filtered.length">
  <table class="table is-bordered is-striped is-fullwidth">
    <thead>
      <tr>
        <th>&nbsp;</th>
        <th v-for="key in columns"
            :key="key"
            @click="sortBy(key)"
            :class="{ active: sortKey == key }">
          {{ capitalize(key) }}
          <span class="arrow" :class="sortOrders[key] > 0 ? 'asc' : 'dsc'">
          </span>
        </th>
      </tr>
    </thead>
    <tbody>
      <contact-row
        v-for="entry in filtered"
        :key="entry.id"
        :entry="entry"
        :columns="columns" />
    </tbody>
  </table>
  </div>
  <p v-else>No matches found.</p>
</template>

<style scoped>
  table thead th {
    position: sticky;
    top: 0;
    width: 60px;
    font-size: 14px;
    vertical-align: middle;
  }
</style>
