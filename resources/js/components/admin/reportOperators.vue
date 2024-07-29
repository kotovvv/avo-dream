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
                    reportCalls();
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
                    reportCalls();
                  "
                ></v-date-picker>
              </v-menu>
            </v-col>
          </v-row>
        </div>
      </v-col>
      <v-col cols="3" class="my-3">
        <v-select
          v-model="filterGeo"
          :items="a_geo"
          item-text="name"
          item-value="name"
          label="GEO"
          multiple
          clearable
          @change="filter"
          outlined
          rounded
        ></v-select
      ></v-col>
      <v-col cols="3" class="align-center">
        <v-select
          v-model="filterOperator"
          :items="a_operator"
          label="Операторы"
          item-text="name"
          item-value="name"
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
    <v-card class="mx-auto" id="operator">
      <v-card-text>
        <v-simple-table>
          <template v-slot:default>
            <thead>
              <tr>
                <th>Оператор</th>
                <th>Всего звонков</th>
                <th>Недозвон</th>
                <th>1-15 Дозвон</th>
                <th>Хороший дозвон</th>
                <th>Общий дозвон</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="op in filterItems" :key="op.NAME + op.geo">
                <td>{{ op.NAME }} {{ op.geo }}</td>
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
  name: "ReportOperators",
  data() {
    return {
      loading: false,
      dateFrom: false,
      dateTo: false,
      dateTimeFrom: new Date(new Date().setDate(new Date().getDate() - 7))
        .toISOString()
        .substring(0, 10),
      dateTimeTo: new Date().toISOString().substring(0, 10),
      operators: [],
      geo: [],
      filterItems: [],
      filterGeo: [],
      filterOperator: [],
      a_geo: [],
      a_operator: [],
    };
  },
  mounted() {
    this.reportCalls();
  },
  methods: {
    filter() {
      this.filterItems = this.geo.filter((g) => {
        return (
          (this.filterGeo.length == 0 || this.filterGeo.includes(g.geo)) &&
          (this.filterOperator.length == 0 ||
            this.filterOperator.includes(g.NAME))
        );
      });
    },
    reportCalls() {
      const self = this;
      let data = {};
      self.loading = true;
      data.dateFrom = self.dateTimeFrom;
      data.dateTo = self.dateTimeTo;
      axios
        .post("/api/reportCalls", data)
        .then((res) => {
          self.a_operator = res.data.operators;
          self.a_geo = res.data.geos;
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
#operator td {
  font-size: 1.2rem !important;
}
</style>
