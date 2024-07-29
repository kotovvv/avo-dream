<template>
  <v-container fluid id="profit">
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
                    getProfit();
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
                    getProfit();
                  "
                ></v-date-picker>
              </v-menu>
            </v-col>
          </v-row>
        </div>
      </v-col>

      <v-col class="align-center">
        <v-radio-group v-model="filterWho" @change="getProfit()" row>
          <v-radio label="Депозиторы" :value="1"></v-radio>
          <v-radio label="Регистраторы" :value="2"></v-radio>
          <v-radio label="Recovery" :value="3"></v-radio>
          <v-radio label="Все" :value="0"></v-radio>
        </v-radio-group>
      </v-col>
      <v-col class="align-center">
        <v-radio-group v-model="filterType" @change="getProfit()" row>
          <v-radio label="CPL" value="L"></v-radio>
          <v-radio label="CPA" value="A"></v-radio>
          <v-radio label="Все" value=""></v-radio>
        </v-radio-group>
      </v-col>
    </v-row>
    <v-row>
      <v-progress-linear
        :active="loading"
        :indeterminate="loading"
        color="deep-purple accent-4"
      ></v-progress-linear>
    </v-row>
    <v-row>
      <v-col cols="12">
        <v-card class="mx-auto" id="profit">
          <v-card-text>
            <v-row>
              <v-col cols="9">
                <v-simple-table>
                  <template v-slot:default>
                    <thead>
                      <tr>
                        <th>Поставщик</th>
                        <th>Тип</th>
                        <th>Кол лидов</th>
                        <th>Сумма затрат</th>
                        <th>Кол-во deposit</th>
                        <th>Сумма pending</th>
                        <th>Сумма deposit</th>
                        <th>Общая прибыль</th>
                        <th>Профит</th>
                        <th>% профита</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in filteredItems" :key="item.id">
                        <td>
                          {{
                            providers.find((p) => {
                              return p.id == item.provider_id;
                            }).name
                          }}
                        </td>
                        <td>{{ item.c_p }}</td>
                        <td>{{ item.hm }}</td>
                        <td>{{ item.s_s }}</td>
                        <td>{{ item.dephm }}</td>
                        <td>{{ item.pending }}</td>
                        <td>{{ item.deposit }}</td>
                        <td>{{ item.profit }}</td>
                        <td
                          :class="[
                            item.a_profit < 0
                              ? 'red--text text--lighten-1'
                              : 'green--text text--darken-2',
                          ]"
                        >
                          {{ item.a_profit }}
                        </td>
                        <td
                          :class="[
                            item.a_profit < 0
                              ? 'red--text text--lighten-1'
                              : 'green--text text--darken-2',
                          ]"
                        >
                          {{ item.perc ? item.perc + "%" : "" }}
                        </td>
                      </tr>
                    </tbody>
                  </template>
                </v-simple-table>
              </v-col>
              <v-col cols="3">
                <v-data-table
                  :headers="headers"
                  :items="providers_wout"
                  class="elevation-1"
                  group-by="group"
                  show-group-by
                  :items-per-page="100"
                  height="70vh"
                  :expanded.sync="expanded"
                >
                  <template
                    v-slot:group.header="{ group, headers, toggle, isOpen }"
                  >
                    <td :colspan="headers.length">
                      <v-btn
                        @click="toggle"
                        small
                        icon
                        :ref="group"
                        :data-open="isOpen"
                      >
                        <v-icon v-if="isOpen">mdi-chevron-up</v-icon>
                        <v-icon v-else>mdi-chevron-down</v-icon>
                        {{ group }}
                      </v-btn>
                    </td>
                  </template>
                  <template v-slot:expanded-item="{ headers, item }">
                    <td :colspan="headers.length">
                      <v-checkbox
                        v-model="selectedProviders"
                        :label="item.name"
                        :value="item.id"
                        hide-details
                      >
                      </v-checkbox>
                      {{ item.related_provider_ids }}
                    </td>
                  </template>
                  <template v-slot:item="{ item }">
                    <div>
                      <v-checkbox
                        v-model="selectedProviders"
                        :label="item.name"
                        :value="item.id"
                        hide-details
                      >
                      </v-checkbox>
                    </div>

                    <div
                      v-if="
                        item.related_provider_ids &&
                        item.related_provider_ids.length > 0 &&
                        Array.isArray(item.related_provider_ids)
                      "
                    >
                      <v-checkbox
                        v-for="chp in providers.filter((f) => {
                          return item.related_provider_ids.includes(f.id);
                        })"
                        :key="'ch' + chp.id"
                        v-model="selectedProviders"
                        :label="chp.name"
                        :value="chp.id"
                        hide-details
                        class="ml-5 children"
                      ></v-checkbox>
                    </div>
                  </template>
                </v-data-table>
              </v-col>
            </v-row>
          </v-card-text> </v-card
      ></v-col>
    </v-row>
  </v-container>
</template>

<script>
import axios from "axios";
import _ from "lodash";
export default {
  name: "ReportProfit",
  data() {
    return {
      loading: false,
      dateFrom: false,
      dateTo: false,
      dateTimeFrom: new Date(new Date().setDate(new Date().getDate() - 7))
        .toISOString()
        .substring(0, 10),
      dateTimeTo: new Date().toISOString().substring(0, 10),
      selectedProviders: [],

      filterWho: 0,
      filterType: "",

      items: [],
      filteredItems: [],
      providers: [],
      providers_wout: [],
      children_ids: [],
      headers: [
        { text: "Провайдеры", value: "name", groupable: false },
        { text: "Связанные", value: "group", groupable: true },
      ],
      expanded: [],
      togglers: {},
    };
  },
  mounted() {
    this.getProfit();
  },
  watch: {
    selectedProviders(newName) {
      this.filteredItems = this.items.filter((i) => {
        return (
          this.selectedProviders.length == 0 ||
          this.selectedProviders.includes(i.provider_id)
        );
      });
    },
  },
  methods: {
    closeAll() {
      Object.keys(this.$refs).forEach((k) => {
        //console.log(this.$refs[k]);
        if (this.$refs[k] && this.$refs[k].$attrs["data-open"]) {
          this.$refs[k].$el.click();
        }
      });
    },
    getProfit() {
      const self = this;
      let data = {};
      self.children_ids = [];
      self.loading = true;
      data.dateFrom = self.dateTimeFrom;
      data.dateTo = self.dateTimeTo;
      data.who = self.filterWho;
      data.type = self.filterType;
      axios
        .post("/api/getProfit", data)
        .then((res) => {
          self.items = res.data.items;
          self.items = self.items.map((i) => {
            i.c_p = i.cp ?? i.cpp;
            i.s_s = i.s ?? i.spp;
            if (i.deposit || i.pending) {
              if (i.c_p == "A") {
                let dep =
                  i.deposit != null ? parseFloat(i.deposit) : parseFloat(0);
                i.profit = parseFloat(dep + parseInt(i.pending)).toFixed(2);
                i.a_profit = i.profit - parseInt(i.dephm) * parseInt(i.s_s);
              } else {
                let dep =
                  i.deposit != null ? parseFloat(i.deposit) : parseFloat(0);
                i.profit = parseFloat(dep + parseInt(i.pending)).toFixed(2);
                i.a_profit = i.profit - i.s_s;
              }
            } else {
              i.profit = 0;
              i.a_profit = 0;
            }

            i.perc = 0.0;
            if (parseInt(i.s_s)) {
              i.perc =
                parseFloat(parseFloat((i.profit / i.s_s) * 100).toFixed(2)) ??
                0.0;
            }
            return i;
          });
          self.items = _.sortBy(self.items, "perc").reverse();

          self.providers = res.data.providers;
          if (Array.isArray(self.providers)) {
            self.providers = self.providers.map(function (p) {
              p.group = "Свободные";
              if (p.related_provider_ids && p.related_provider_ids.length > 0) {
                p.related_provider_ids = JSON.parse(p.related_provider_ids);
                p.group = p.name;

                if (Array.isArray(p.related_provider_ids)) {
                  p.related_provider_ids.forEach((e) => {
                    self.children_ids.push(e);
                    let ix = self.providers.findIndex((f) => {
                      return f.id == e;
                    });
                    if (ix != -1) {
                      self.providers[ix].group = p.name;
                    }
                  });
                }
              }
              return p;
            });

            if (Array.isArray(self.children_ids)) {
              self.providers_wout = self.providers.filter((f) => {
                return !self.children_ids.includes(f.id);
              });
            }
          }
          self.selectedProviders = [];
          self.loading = false;
          setTimeout(() => {
            // wait and then close all groups
            self.closeAll();
          }, 300);
        })
        .catch((error) => console.log(error));
    },
  },
};
</script>

<style lang="scss" scoped>
#profit td {
  font-size: 1.2rem !important;
}
</style>
