<template>
  <v-card
    title="Feladatok"
    flat
    class="elevation-17 !tw-rounded-xl !tw-bg-[rgba(18,20,24,0.55)] tw-backdrop-blur-3xl"
  >
    <v-data-table-server
      class="!tw-bg-transparent"
      v-model="selected"
      :items-per-page="itemsPerPage"
      :headers="headers"
      :items="serverItems"
      :items-length="totalItems"
      :loading="isLoading"
      :loading-text="'Feladatok betöltése...'"
      item-value="id"
      select-strategy="page"
      show-select
      @update:options="loadTasks"
      :no-data-text="'Jeeej! Nincs feladat!'"
      :page-text="'{0}-{1} / {2}'"
      :items-per-page-text="''"
      :items-per-page-options="[5, 10, 15, 20, 50, 100]"
    >
      <template v-slot:top>
        <v-toolbar color="transparent">
          <v-text-field
            class="tw-mx-4 tw-max-w-96"
            label="Keresés..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            hide-details
            single-line
            clearable
            @keydown.enter="filterBySearch($event.target.value)"
          />

          <v-dialog v-model="dialog" max-width="1024">
            <template v-slot:activator="{ on, attrs }">
              <v-btn
                color="green"
                variant="tonal"
                dark
                class="!tw-h-[56px]"
                v-bind="attrs"
                v-on="on"
                @click="setupAddTask"
              >
                Új hozzáadása
              </v-btn>

              <v-btn
                color="red"
                variant="tonal"
                dark
                class="tw-ml-2 !tw-h-[56px]"
                v-bind="attrs"
                v-on="on"
                :disabled="selected.length === 0"
                @click="deleteTasks"
              >
                Törlés
              </v-btn>
            </template>

            <v-card
              class="!tw-rounded-xl !tw-bg-[rgba(48,36,63,0.85)] tw-backdrop-blur-3xl"
            >
              <v-card-title>
                <span class="headline">{{ dialogTitle }}</span>
              </v-card-title>
              <v-card-text>
                <v-container>
                  <v-row>
                    <v-col cols="12" sm="12" md="12">
                      <VTextarea
                        v-model="editedTask.description"
                        label="Feladat leírása"
                      />
                    </v-col>
                  </v-row>
                  <v-row>
                    <v-col cols="12" sm="6" md="4">
                      <fieldset>
                        <legend class="tw-mb-2">Időigény</legend>

                        <div class="tw-flex tw-gap-2">
                          <v-text-field
                            v-model="editedTask.estimated_time.days"
                            label="nap"
                            type="number"
                          />
                          <v-text-field
                            v-model="editedTask.estimated_time.hours"
                            label="óra"
                            type="number"
                          />
                          <v-text-field
                            v-model="editedTask.estimated_time.minutes"
                            label="perc"
                            type="number"
                          />
                        </div>
                      </fieldset>
                    </v-col>
                    <v-col cols="12" sm="0" md="8"></v-col>
                    <v-col cols="12" sm="0" md="4">
                      <v-autocomplete
                        label="Felelős"
                        v-model="editedTask.selectedUser"
                        :items="searchedUsers"
                        item-value="user_id"
                        item-title="user_name"
                        :no-data-text="'Nincs találat'"
                        hide-no-data
                        return-object
                        @update:focused="fetchUsers"
                        @update:search="fetchUsers"
                        clearable
                      ></v-autocomplete>
                    </v-col>
                    <v-col cols="12" sm="0" md="8"></v-col>
                  </v-row>
                </v-container>
              </v-card-text>
              <v-card-actions>
                <v-spacer />
                <v-btn color="red" variant="tonal" @click="dialog_close">Mégse</v-btn>
                <v-btn color="green" variant="tonal" @click="dialog_save">Mentés</v-btn>
              </v-card-actions>
            </v-card>
          </v-dialog>
        </v-toolbar>

        <v-card class="!tw-bg-transparent !tw-shadow-none tw-w-min tw-mt-5">
          <v-cardSubtitle>
            ∑ Időigény:
            {{
              totalEstimatedTime() <= 0 ? "-" : formatSeconds(totalEstimatedTime())
            }}</v-cardSubtitle
          >
        </v-card>

        <v-card class="!tw-bg-transparent !tw-shadow-none tw-w-min tw-mt-1">
          <v-cardSubtitle>
            ∑ Felhasznált idő:
            {{
              totalUsedTime() <= 0 ? "-" : formatSeconds(totalUsedTime())
            }}</v-cardSubtitle
          >
        </v-card>
      </template>

      <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
        <tr>
          <template v-for="column in columns">
            <template v-if="column.key === 'data-table-select'">
              <th :key="column.key" class="!tw-px-2 !tw-w-[56px] !tw-pb-2">
                <!-- 
                <VCheckbox :input-value="column.selected" hide-details />
                -->
              </th>
            </template>
            <template v-else>
              <th
                :key="column.key"
                class="!tw-h-20 !tw-pb-2"
                :class="{
                  'cursor-pointer hover:tw-bg-[rgba(255,255,255,0.0875)]':
                    column.sortable,
                }"
                :style="{
                  minWidth: column.minWidth,
                  maxWidth: column.maxWidth,
                  width: column.width,
                }"
                @click="() => column.sortable && !isLoading && toggleSort(column)"
              >
                <span
                  class="mr-1 tw-inline-block"
                  :class="{
                    'tw-text-emerald-300': isSorted(column),
                  }"
                >
                  {{ column.title }}
                </span>
                <template v-if="isSorted(column)">
                  <VIcon :icon="getSortIcon(column)" class="!tw-text-lg"></VIcon>
                </template>
              </th>
            </template>
          </template>
        </tr>
      </template>

      <template v-slot:[`item.description`]="{ item }">
        {{ item.description }}
      </template>

      <template v-slot:[`item.estimated_time`]="{ item }">
        <VChip :color="colorBySeconds(item.estimated_time)">
          {{ formatSeconds(item.estimated_time) }}
        </VChip>
      </template>

      <template v-slot:[`item.used_time`]="{ item }">
        <VChip
          :color="colorBySeconds(item.used_time)"
          :class="item.used_time === null ? '!tw-hidden' : ''"
        >
          {{ formatSeconds(item.used_time) }}
        </VChip>
      </template>

      <template v-slot:[`item.actions`]="{ item }">
        <div class="tw-flex tw-gap-2 actions">
          <div>
            <VIcon size="26" @click="setupEditTask(item)"> mdi-pencil </VIcon>
            <VTooltip activator="parent" location="bottom" open-delay="750">
              Szerkesztés
            </VTooltip>
          </div>
          <div>
            <VIcon
              size="26"
              @click="setTaskCompleted(item)"
              :color="item.completed_at ? 'green' : 'default'"
            >
              mdi-check
            </VIcon>
            <VTooltip activator="parent" location="bottom" open-delay="750">
              {{ item.completed_at ? "Befejezve" : "Befejezés" }}
            </VTooltip>
          </div>
        </div>
      </template>
    </v-data-table-server>

    <ConfirmDialog ref="confirm" />
  </v-card>
</template>

<style lang="scss" scoped>
:deep(.v-table__wrapper) {
  border-radius: 0;
  margin-top: 8px;
}

:deep(.v-data-table__tr:hover) {
  box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.16),
    inset 0 1px 0 rgba(255, 255, 255, 0.12);
}

:deep(.actions > *) {
  opacity: 0.85;
}
:deep(.actions > *:hover) {
  opacity: 1;
}

:deep(td) {
  color: rgba(255, 255, 255, 0.87);

  padding-top: 0.5rem !important;
  padding-bottom: 0.5rem !important;
}

:deep(.v-checkbox-btn i) {
  color: #fff;
}

:deep(.v-overlay__scrim) {
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(12px);
  opacity: 0.5;
}
</style>

<script>
import axios from "axios";

import ConfirmDialog from "../components/ConfirmDialog.vue";

export default {
  data: () => ({
    headers: [
      {
        title: "Feladat",
        key: "description",
        align: "start",
        sortable: false,
        minWidth: "260px",
      },
      {
        title: "Létrehozva",
        key: "created_at",
        align: "left",
        minWidth: "175px",
      },
      {
        title: "Időigény",
        key: "estimated_time",
        align: "left",
        minWidth: "140px",
      },
      {
        title: "Felhasznált idő",
        key: "used_time",
        align: "left",
        minWidth: "175px",
      },
      {
        title: "Befejezve",
        key: "completed_at",
        align: "left",
        minWidth: "175px",
      },
      {
        title: "Felelős",
        key: "user_name",
        align: "left",
        minWidth: "164px",
      },
      {
        title: "",
        key: "actions",
        align: "left",
        sortable: false,
      },
    ],

    page: 1,
    itemsPerPage: 5,
    sortBy: [],
    search: "",
    serverItems: [],
    selected: [],
    isLoading: true,
    totalItems: 0,

    dialog: false,
    dialogTitle: null,

    editedTask: {
      id: -1,
      description: "",
      estimated_time: {
        days: 0,
        hours: 0,
        minutes: 0,
      },
      completed_at: "",
      selectedUser: {
        user_id: null,
        user_name: null,
      },
    },

    defaultTaskSettings: {
      id: -1,
      description: "",
      estimated_time: {
        days: 0,
        hours: 0,
        minutes: 0,
      },
      completed_at: "",
      selectedUser: {
        user_id: null,
        user_name: null,
      },
    },

    isSearchingUsers: false,
    searchedUsers: [],
  }),

  watch: {},

  components: {
    ConfirmDialog: ConfirmDialog,
  },

  methods: {
    async loadTasks({ page, itemsPerPage, sortBy }) {
      this.page = page;
      this.itemsPerPage = itemsPerPage;
      this.sortBy = sortBy;

      this.isLoading = true;

      const start = (page - 1) * itemsPerPage;
      const tasks = await this.fetchTasks(start, itemsPerPage);

      const total = await this.countTasks();

      this.serverItems = tasks;
      this.totalItems = total;
      this.isLoading = false;
    },

    async fetchTasks($offset = 0, $limit = 5) {
      let params = {
        offset: $offset,
        limit: $limit,
        search: this.search,
      };

      if (this.sortBy.length) {
        params.sort_by = this.sortBy[0].key;
        params.order = this.sortBy[0].order === "desc" ? "desc" : "asc";
      }

      const response = await axios.get("/api/tasks", { params });
      if (response.status !== 200) {
        console.error("Failed to fetch tasks");
        return [];
      }
      return response.data;
    },

    async countTasks() {
      const response = await axios.get("/api/tasks/count");
      if (response.status !== 200) {
        console.error("Failed to fetch tasks count");
        return 0;
      }
      return response.data.count;
    },

    filterBySearch(previousSearch) {
      if (!this.isLoading && this.search != previousSearch) {
        this.search = previousSearch;
        this.page = 1;
        this.pageText = "{0}-{1} / {2}";
        this.loadTasks({
          page: this.page,
          itemsPerPage: this.itemsPerPage,
          sortBy: this.sortBy,
        });
      }
    },

    async fetchUsers(name = "") {
      if (this.isSearchingUsers) return;

      this.isSearchingUsers = true;

      if (typeof name !== "string") name = "";

      const params = {
        limit: 10,
      };

      if (name) params.name = name;

      const response = await axios.get("/api/users", { params });
      this.isSearchingUsers = false;

      let data = [];

      if (response.status !== 200) {
        console.error("Failed to fetch users");
      } else {
        data = response.data;
      }

      this.searchedUsers = data.map((user) => {
        return {
          user_id: user.id,
          user_name: user.name,
          user_email: user.email,
        };
      });

      return data;
    },

    setupAddTask(item) {
      this.dialogTitle = "Feladat hozzáadása";

      this.editedTask.id = this.defaultTaskSettings.id;
      this.editedTask.description = this.defaultTaskSettings.description;
      this.editedTask.estimated_time = this.defaultTaskSettings.estimated_time;
      this.editedTask.selectedUser.user_id = this.defaultTaskSettings.selectedUser.user_id;
      this.editedTask.selectedUser.user_name = this.defaultTaskSettings.selectedUser.user_name;

      this.dialog = true;
    },

    async addTask() {
      try {
        await axios.post("/api/tasks", {
          description: this.editedTask.description,
          estimated_time: this.daysHoursMinutesToSeconds(
            this.editedTask.estimated_time.days,
            this.editedTask.estimated_time.hours,
            this.editedTask.estimated_time.minutes
          ),
          user_id: this.editedTask.selectedUser.user_id,
        });

        this.loadTasks({
          page: this.page,
          itemsPerPage: this.itemsPerPage,
          sortBy: this.sortBy,
        });

        this.dialog = false;
      } catch (error) {
        if (error) console.error("Failed to add task", error);
      }
    },

    setupEditTask(item) {
      this.dialogTitle = "Feladat szerkesztése";

      this.editedTask.id = item.id;
      this.editedTask.description = item.description;
      this.editedTask.estimated_time = this.secondsToDaysHoursMinutes(
        item.estimated_time
      );
      this.editedTask.selectedUser.user_id = item.user_id;
      this.editedTask.selectedUser.user_name = item.user_name;

      this.dialog = true;
    },

    async editTask() {
      try {
        await axios.put(`/api/tasks/${this.editedTask.id}`, {
          description: this.editedTask.description,
          estimated_time: this.daysHoursMinutesToSeconds(
            this.editedTask.estimated_time.days,
            this.editedTask.estimated_time.hours,
            this.editedTask.estimated_time.minutes
          ),
          user_id: this.editedTask.selectedUser.user_id,
        });

        this.loadTasks({
          page: this.page,
          itemsPerPage: this.itemsPerPage,
          sortBy: this.sortBy,
        });

        this.dialog = false;
      } catch (error) {
        if (error) console.error("Failed to edit task", error);
      }
    },

    async deleteTasks() {
      try {
        await this.$refs.confirm.open(
          "Megerősítés",
          "Biztos törölni akarod a kijelölt feladatokat?"
        );

        await axios.delete("/api/tasks", {
          data: {
            ids: this.selected,
          },
        });

        this.selected = [];
        this.loadTasks({
          page: this.page,
          itemsPerPage: this.itemsPerPage,
          sortBy: this.sortBy,
        });
      } catch (error) {
        if (error) console.error("Failed to delete tasks", error);
      }
    },

    async setTaskCompleted(item) {
      if (item.completed_at) return;

      try {
        await this.$refs.confirm.open("Megerősítés", "Biztos késznek jelölöd?");

        const response = await axios.put(`/api/tasks/${this.editedTask.id}/complete`, {
          id: item.id,
        });
        this.loadTasks({
          page: this.page,
          itemsPerPage: this.itemsPerPage,
          sortBy: this.sortBy,
        });
      } catch {
        // nothing to do, canceled
      }
    },

    dialog_close() {
      this.dialog = false;
    },

    dialog_save() {
      if (this.editedTask.id === this.defaultTaskSettings.id) {
        this.addTask();
      } else {
        this.editTask();
      }
    },

    colorBySeconds(seconds, inverted = false) {
      let values = ["red", "orange", "green"];
      if (inverted) values.reverse();

      const hours = seconds / 60 / 60;

      // if more than 6 hours
      if (hours > 24) return values[0];
      // if more than 3 hours
      else if (hours > 12) return values[1];
      // if less than 3 hours
      else return values[2];
    },

    formatSeconds(seconds) {
      seconds = Math.floor(seconds);
      let minutes = Math.floor(seconds / 60);
      let hours = Math.floor(minutes / 60);
      let days = Math.floor(hours / 24);

      seconds %= 60;
      minutes %= 60;
      hours %= 24;

      let components = [];
      if (days > 0) {
        components.push(days + " nap");
      }
      if (hours > 0) {
        components.push(hours + " óra");
      }
      if (minutes > 0) {
        components.push(minutes + " perc");
      }
      if (components.length === 0) {
        components.push("< 1 perc");
      }

      return components.join(", ");
    },

    secondsToDaysHoursMinutes(seconds) {
      seconds = Math.floor(seconds);
      let minutes = Math.floor(seconds / 60);
      let hours = Math.floor(minutes / 60);
      let days = Math.floor(hours / 24);

      seconds %= 60;
      minutes %= 60;
      hours %= 24;

      return { days, hours, minutes };
    },

    daysHoursMinutesToSeconds(days, hours, minutes) {
      return days * 24 * 60 * 60 + hours * 60 * 60 + minutes * 60;
    },

    totalEstimatedTime() {
      return this.serverItems.reduce((total, item) => {
        if (this.selected.includes(item.id)) {
          return total + item.estimated_time;
        }
        return total;
      }, 0);
    },

    totalUsedTime() {
      return this.serverItems.reduce((total, item) => {
        if (this.selected.includes(item.id)) {
          return total + item.used_time;
        }
        return total;
      }, 0);
    },
  },
};
</script>
