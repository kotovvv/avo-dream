<template>
  <v-container>
    <v-row class="align-center justify-center">
      <v-col cols="3" class="my-3">
        <div class="status_wrp wrp_date pl-5">
          <v-row align="center">
            <v-col>
              <v-menu
                v-model="dateFrom"
                :close-on-content-click="false"
                :nudge-right="40"
                transition="scale-transition"
                offset-y
                min-width="auto"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field
                    v-model="dateTimeFrom"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                  ></v-text-field>
                </template>
                <v-date-picker
                  locale="ru-ru"
                  v-model="dateTimeFrom"
                  @input="
                    dateFrom = false;
                    reportManagerCalls();
                  "
                ></v-date-picker>
              </v-menu>
            </v-col>
            <v-col>
              <v-menu
                v-model="dateTo"
                :close-on-content-click="false"
                :nudge-right="40"
                transition="scale-transition"
                offset-y
                min-width="auto"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field
                    v-model="dateTimeTo"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                  ></v-text-field>
                </template>
                <v-date-picker
                  locale="ru-ru"
                  v-model="dateTimeTo"
                  @input="
                    dateTo = false;
                    reportManagerCalls();
                  "
                ></v-date-picker>
              </v-menu>
            </v-col>
          </v-row>
        </div>
      </v-col>

      <v-col cols="3" class="align-center">
        <v-select
          v-model="filterManager"
          :items="a_manager"
          label="Менеджеры"
          item-text="fio"
          item-value="id"
          multiple
          clearable
          @change="filter"
          outlined
          rounded
        ></v-select
      ></v-col>
      <v-col cols="3" class="align-center">
        <v-select
          v-model="filterGroup"
          :items="a_group"
          label="Группы"
          item-text="fio"
          item-value="id"
          multiple
          clearable
          @change="filter"
          outlined
          rounded
        ></v-select
      ></v-col>
    </v-row>
    <v-row>
      <v-progress-linear
        :active="loading"
        :indeterminate="loading"
        color="deep-purple accent-4"
      ></v-progress-linear>
    </v-row>
    <v-card class="mx-auto" id="manager">
      <v-card-text>
        <v-simple-table>
          <template v-slot:default>
            <thead>
              <tr>
                <th>Менеджер</th>
                <th>Всего звонков</th>
                <th>Недозвон</th>
                <th>1-15 Дозвон</th>
                <th>Хороший дозвон</th>
                <th>Общий дозвон</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="op in filterItems" :key="op.fio">
                <td>{{ op.fio }}</td>
                <td>{{ op.allgeo }}</td>
                <td>
                  {{ op.badgeo }} -
                  <span class="blue-grey--text"
                    >{{ parseInt((op.badgeo / op.allgeo) * 100) }}%</span
                  >
                </td>
                <td>
                  {{ op.callgeo }} -
                  <span class="blue-grey--text"
                    >{{ parseInt((op.callgeo / op.allgeo) * 100) }}%</span
                  >
                </td>
                <td>
                  {{ op.goodgeo }} -
                  <span class="blue-grey--text"
                    >{{ parseInt((op.goodgeo / op.allgeo) * 100) }}%</span
                  >
                </td>
                <td>
                  {{ op.allgood }} -
                  <span class="blue-grey--text"
                    >{{ parseInt((op.allgood / op.allgeo) * 100) }}%</span
                  >
                </td>
              </tr>
            </tbody>
          </template>
        </v-simple-table>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script>
import axios from "axios";

export default {
  name: "ReportManagerCalls",
  data() {
    return {
      loading: false,
      dateFrom: false,
      dateTo: false,
      dateTimeFrom: new Date(new Date().setDate(new Date().getDate() - 7))
        .toISOString()
        .substring(0, 10),
      dateTimeTo: new Date().toISOString().substring(0, 10),
      managers: [],
      geo: [],
      filterItems: [],

      filterManager: [],
      filterGroup: [],

      a_manager: [],
      a_group: [],
    };
  },
  mounted() {
    this.reportManagerCalls();
  },
  methods: {
    filter() {
      this.filterItems = this.geo.filter((g) => {
        return (
          (this.filterManager.length == 0 ||
            this.filterManager.includes(g.user_id)) &&
          (this.filterGroup.length == 0 ||
            this.filterGroup.includes(g.group_id))
        );
      });
    },
    reportManagerCalls() {
      const self = this;
      let data = {};
      self.loading = true;
      data.dateFrom = self.dateTimeFrom;
      data.dateTo = self.dateTimeTo;
      axios
        .post("/api/reportManagerCalls", data)
        .then((res) => {
          self.a_manager = res.data.users;
          self.a_group = res.data.groups;

          self.geo = res.data.geo;
          self.loading = false;
          this.filter();
        })
        .catch((error) => console.log(error));
    },
  },
};
</script>

<style lang="scss" scoped>
#manager td {
  font-size: 1.2rem !important;
}
</style>
