<template>
  <v-dialog
    v-model="dialog"
    :max-width="options.width"
    :style="{ zIndex: options.zIndex }"
    @keydown.esc="cancel"
  >
    <v-card class="!tw-rounded-xl !tw-bg-[rgba(48,36,63,0.85)] tw-backdrop-blur-3xl">
      <v-toolbar dark :color="options.color" dense flat>
        <v-toolbar-title class="text-body-2 font-weight-bold grey--text">
          {{ title }}
        </v-toolbar-title>
      </v-toolbar>
      <v-card-text v-show="!!message" class="pa-4 black--text">
        <div>
          {{ message }}
        </div>
      </v-card-text>
      <v-card-actions class="pt-3">
        <v-spacer></v-spacer>
        <v-btn
          v-if="!options.noconfirm"
          color="grey"
          text
          class="body-2 font-weight-bold"
          @click="cancel"
        >
          Mégse
        </v-btn>
        <v-btn color="green" class="body-2 font-weight-bold" outlined @click="agree">
          OK
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style lang="scss" scoped>
:deep(.v-overlay__scrim) {
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(12px);
  opacity: 0.5;
}
</style>

<script>
export default {
  data: function () {
    return {
      dialog: false,
      resolve: null,
      reject: null,
      message: null,
      title: null,
      options: {
        color: "rgba(255,255,255,0.05)",
        width: 400,
        zIndex: 200,
        noconfirm: false,
      },
    };
  },
  methods: {
    open(title, message, options) {
      this.dialog = true;
      this.title = title;
      this.message = message;
      this.options = Object.assign(this.options, options);
      return new Promise((resolve, reject) => {
        this.resolve = resolve;
        this.reject = reject;
      });
    },
    agree() {
      this.dialog = false;
      if (typeof this.resolve === "function") {
        this.resolve();
      }
    },
    cancel() {
      this.dialog = false;
      if (typeof this.reject === "function") {
        this.reject();
      }
    },
  },
};
</script>
