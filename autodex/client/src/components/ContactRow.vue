<script lang="ts">
import { PropType } from 'vue';
import { Contact } from '../../../lib/types';
import ClickToEdit from "./ClickToEdit.vue";

export default {
    props: {
        entry: { type: Object as PropType<Contact>, required: true },
        columns: { type: Array as PropType<string[]>, required: true },
    },
    data() {
        return {
            names: ['contact', 'email', 'organization', 'address1', 'address2', 'city'],
            editing: "",
      };
    },
    components: {
        ClickToEdit
    },
    methods: {
        changed(request: { name: string, value: string }) {
            console.log("CHANGED!");
            console.log(request);
        },
        edit(key: string) {
            console.log("edit td");
            console.log(key);
            this.editing = key;
        },
        isText(key: string) {
            if (this.names.includes(key)) {
                return true;
            }

            return false;
        },
        trash(entry: Contact) {
            const name = entry.contact || entry.organization || entry.id;
            if (confirm(`OK to delete ${name}?`)) {
                this.$emit("trash-contact", entry);
            }
        }
    },
};
</script>

<template>
  <tr>
    <td @click="edit('')">&nbsp;</td>
    <td v-for="key in columns" :key="key" @click="edit(key)" @blur="edit('')">
      <click-to-edit :name="key" :editing="editing" :value="entry[key]" v-on:changed="changed" />
    </td>
  </tr>
</template>

<style scoped>
  tr td {
      padding: 0px 0px 0px 4px;
      font-size: 14px;
      height: 22px;
      vertical-align: middle;
      text-overflow: clip;
      white-space: nowrap;
  }
</style>
