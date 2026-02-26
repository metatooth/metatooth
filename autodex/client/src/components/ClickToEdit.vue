<script lang="ts">
import { PropType } from 'vue';

export default {
    props: {
        name: { type: String, required: true },
        value: { type: String, required: true },
        editing: { type: String }
    },
    data() {
        return {
            local: this.value,
        };
    },
    computed: {
        formatted() {
            return this.local.slice(0, 24);
        }
    },
    methods: {
        edit(event: MouseEvent) {
            console.log("EDIT");
            console.log(event);
            //window.addEventListener("keydown", this.keyHandler);
//this.editing = true;
        },
        keyHandler(event: MouseEvent) {
/**
console.log("KEY");
            console.log(event);
            //console.log(event.key);
            //window.removeEventListener("keydown", this.keyHandler);

            if (event.key == 'Escape') {
                this.local = this.value;
  //              this.editing = false;
            } else if (event.key == 'Tab') {
                this.save();
    //            this.editing = false;
}
*/
        },
        save() {
            this.$emit("changed", { name: this.name, value: this.local });
        },
    },
};
</script>

<template>
    <input ref="input"
           type="text"
           size="10"
           v-if="editing == name"
           v-model="local"
           class="form-control" />
    <span v-else>
      {{formatted}}
    </span>
</template>
