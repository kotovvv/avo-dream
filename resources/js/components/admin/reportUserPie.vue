<template>
  <v-container fluid>
    <v-row>
      <v-col cols="3">
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
                    pieUser();
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
                    pieUser();
                  "
                ></v-date-picker>
              </v-menu>
            </v-col>
          </v-row>
        </div>
      </v-col>
      <v-col cols="3">
        <v-text-field
          v-model="search"
          label="Поиск"
          append-icon="mdi-magnify"
          outlined
          rounded
          clearable
        ></v-text-field>
      </v-col>
      <v-col cols="3">
        <!-- statuses_lids -->

        <v-select
          ref="filterStatus"
          color="red"
          label="Фильтр по статусам"
          v-model="filterStatus"
          :items="statuses"
          item-text="name"
          item-value="id"
          outlined
          rounded
          :multiple="true"
          clearable
        >
          <template v-slot:selection="{ item, index }">
            <span v-if="index === 0">{{ item.name }} </span>
            <span v-if="index === 1" class="grey--text text-caption">
              (+{{ filterStatus.length - 1 }} )
            </span>
          </template>
          <template v-slot:item="{ item, attrs }">
            <v-badge
              :value="attrs['aria-selected'] == 'true'"
              color="#7620df"
              dot
              left
            >
              <i
                :style="{
                  background: item.color,
                  outline: '1px solid grey',
                }"
                class="sel_stat mr-4"
              ></i>
            </v-badge>
            {{ item.name }}
          </template>
        </v-select>
      </v-col>
      <v-col
        cols="3"
        v-if="$props.user.role_id == 1 && $props.user.office_id == 0"
      >
        <v-select
          v-model="filterOffices"
          :items="offices"
          item-text="name"
          item-value="id"
          outlined
          rounded
          @change="
            getUsers();
            getPage(0);
          "
        >
        </v-select>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="8">
        <PieChart :datap="chartDataTime" />
        <div class="wrp__statuses pt-5">
          <template v-for="(i, x) in statuses_time">
            <div class="status_wrp" :key="x">
              <b
                :style="{
                  background: i.color,
                  outline: '1px solid' + i.color,
                }"
                >{{ i.hm
                }}{{
                  parseInt((i.hm * 100) / leads.length)
                    ? " - " + parseInt((i.hm * 100) / leads.length) + "%"
                    : ""
                }}</b
              >
              <span>{{ i.name }}</span>
            </div>
          </template>
        </div>
        <p v-if="allqtytel > 0" class="mt-3">
          Количество звонков: {{ allqtytel }}
        </p>
        <div class="wrp__statuses pt-2">
          <template v-for="(i, x) in qtytel">
            <div class="status_wrp" :key="x">
              <b style="background: black; outline: 1px solid grey">
                {{ i.name }}
              </b>
              <span>
                Недозвон: {{ i.nocall }} Меньше 15с: {{ i.low }} Больше 15с:
                {{ i.big }}</span
              >
            </div>
          </template>
        </div>
      </v-col>
      <v-col cols="4" v-if="$props.user.role_id != 3">
        <div class="pa-5 w-100 border wrp_users">
          <div class="my-3">Поиск пользователей</div>
          <v-autocomplete
            v-model="selectedUser"
            :items="users"
            label="Выбор"
            item-text="fio"
            item-value="id"
            :return-object="true"
            append-icon="mdi-close"
            outlined
            rounded
            @click:append="clearuser()"
          ></v-autocomplete>

          <div class="scroll-y">
            <v-list>
              <v-radio-group
                id="usersradiogroup"
                ref="radiogroup"
                v-model="userid"
                v-bind="users"
                @change="
                  group_id = 0;
                  pieUser();
                "
              >
                <div
                  v-for="office in filterOffices == 0
                    ? offices
                    : offices.filter((o) => o.id == filterOffices)"
                  :key="office.id"
                >
                  <p class="title" v-if="office.id > 0">{{ office.name }}</p>
                  <v-expansion-panels v-model="akkvalue[office.id]">
                    <v-expansion-panel
                      v-for="item in group.filter(
                        (g) => g.office_id == office.id
                      )"
                      :key="item.group_id"
                    >
                      <v-expansion-panel-header>
                        <!-- <div
                          height="60"
                          width="60"
                          class="img v-expansion-panel-header__icon mr-1"
                        >
                          {{ item.fio.slice(0, 3) }}
                        </div> -->
                        <v-btn
                          text
                          @click.stop="
                            group_id = item.id;
                            pieUser();
                          "
                          >{{ item.fio }}</v-btn
                        >

                        <div></div>
                      </v-expansion-panel-header>
                      <v-expansion-panel-content>
                        <v-col
                          v-for="user in users.filter(function (i) {
                            return i.group_id == item.group_id;
                          })"
                          :key="user.id"
                        >
                          <v-radio
                            :label="user.fio + ' (' + user.hmlids + ')'"
                            :value="user.id"
                            :disabled="disableuser == user.id"
                          >
                          </v-radio>
                          <!-- <div class="mb-3 grp_btn">
                            <v-btn
                              small
                              :color="usercolor(user)"
                              @click="
                                disableuser = user.id;
                                filterGroups = [];
                                getPage();
                              "
                              :value="user.hmlids"
                              :disabled="disableuser == user.id"
                              >{{ user.hmlids }}</v-btn
                            >
                          </div> -->
                        </v-col>
                      </v-expansion-panel-content>
                    </v-expansion-panel>
                  </v-expansion-panels>
                </div>
              </v-radio-group>
            </v-list>
          </div>
        </div>
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
      <v-col>
        <div class="border pa-4">
          <!-- :search="search"
          :single-select="false"
          v-model.lazy.trim="selected"
          show-select-->
          <v-data-table
            id="tablids"
            :search="search"
            :headers="headers"
            item-key="id"
            @click:row="clickrow"
            :items="filteredItems"
            ref="datatable"
            :footer-props="{
              'items-per-page-options': [50, 10, 100, 250, 500, -1],
              'items-per-page-text': '',
            }"
            :expanded.sync="expanded"
          >
            <template v-slot:expanded-item="{ headers, item }">
              <td :colspan="headers.length" class="blackborder">
                <logtel :lid_id="item.id" :key="item.id" />
              </td>
            </template>
          </v-data-table>
        </div>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import PieChart from "../provider/pieComponents";
import axios from "axios";
import _ from "lodash";
import logtel from "../manager/logtel";
export default {
  name: "ReportUserPie",

  components: {
    PieChart,
    logtel,
  },
  props: ["user"],
  data() {
    return {
      loading: false,
      chartDataTime: {
        labels: [],
        datasets: [
          {
            backgroundColor: [],
            data: [],
          },
        ],
      },
      dateFrom: false,
      dateTo: false,
      dateTimeFrom: new Date(new Date().setDate(new Date().getDate() - 7))
        .toISOString()
        .substring(0, 10),
      dateTimeTo: new Date().toISOString().substring(0, 10),
      selectedUser: {},
      users: [],
      userid: null,
      disableuser: 0,
      offices: [],
      filterOffices: 1,
      akkvalue: [],
      group: [],
      group_id: 0,
      statuses_time: [],
      headers: [
        { text: "Имя", value: "name" },
        { text: "Email", value: "email" },
        // { text: "Название базы", value: "load_mess" },
        { text: "Телефон.", align: "start", value: "tel" },
        // { text: "Афилятор", value: "afilyator" },
        // { text: "Поставщик", value: "provider" },
        // { text: "Менеджер", value: "user" },
        { text: "Создан", value: "date_created" },
        // { text: "Изменён", value: "date_updated" },
        { text: "Статус", value: "status" },
        // { text: "Депозит", value: "depozit" },
        // { text: "Сообщение", value: "text" },
        // { text: "Звонков", value: "qtytel" },
        // { text: "ПЕРЕЗВОН", value: "ontime" },
      ],
      leads: [],
      statuses: [],
      search: "",
      filterStatus: null,
      qtytel: [],
      allqtytel: 0,
      expanded: [],
    };
  },

  mounted() {
    this.getUsers();
    this.getOffices();
    this.getStatuses();
  },
  watch: {
    selectedUser(user) {
      if (user == {}) {
        this.userid = null;
        this.akkvalue = [];
        return;
      }
      //this.disableuser = user.id;
      this.akkvalue = [];
      if (this.group) {
        this.akkvalue[user.office_id] = _.findIndex(
          this.group.filter((g) => g.office_id == user.office_id),
          {
            group_id: user.group_id,
          }
        );
      }
    },
  },
  computed: {
    filteredItems() {
      return this.leads.filter((i) => {
        return (
          !this.filterStatus ||
          this.filterStatus.length == 0 ||
          i.status_id == this.filterStatus
        );
      });
    },
  },
  methods: {
    clickrow(item, row) {
      this.tel = item.tel;
      this.lid_id = item.id;
      if (!row.isExpanded) {
        this.expanded = [item];
      } else {
        this.expanded = [];
      }
    },
    getStatuses() {
      let self = this;
      axios
        .get("/api/statuses")
        .then((res) => {
          self.statuses = res.data.map(({ uname, name, id, color, order }) => ({
            uname,
            name,
            id,
            color,
            order,
          }));
          if (self.$props.user.role_id > 1) {
            self.filterstatuses = self.statuses.filter((e) => e.id != 8);
          } else {
            self.filterstatuses = [...self.statuses];
          }
          // self.statuses.unshift({ name: "Default", id: 0 });
        })
        .catch((error) => console.log(error));
    },
    pieUser() {
      const self = this;
      self.allqtytel = 0;
      let group = "";
      if (self.group_id) {
        group = "/" + self.group_id;
        self.userid = self.group_id;
      }
      self.loading = true;
      axios
        .get(
          "api/pieUser/" +
            self.userid +
            "/" +
            self.dateTimeFrom +
            "/" +
            self.dateTimeTo +
            group
        )
        .then(function (res) {
          self.chartDataTime.labels = res.data.labels;
          self.chartDataTime.datasets[0].backgroundColor =
            res.data.backgroundColor;
          self.chartDataTime.datasets[0].data = res.data.data;
          self.statuses_time = res.data.statuses;
          self.leads = res.data.leads;
          self.leads.map(function (e) {
            // e.user = self.users.find((u) => u.id == e.user_id).fio;
            e.date_created = e.created_at.substring(0, 10);
            // if (self.providers.find((p) => p.id == e.provider_id)) {
            //   e.provider = self.providers.find(
            //     (p) => p.id == e.provider_id
            //   ).name;
            // }
            if (e.status_id)
              e.status = self.statuses.find((s) => s.id == e.status_id).name;
          });
          // self.allLidsTime = self.statuses_time.reduce(
          //   (all, el) => all + el.hm,
          //   0
          // );
          self.qtytel = res.data.qtytel;
          self.allqtytel = self.qtytel.reduce((all, el) => all + el.hm, 0);
          self.loading = false;
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    usercolor(user) {
      return user.role_id == 2 ? "green" : "blue";
    },
    getOffices() {
      let self = this;
      self.filterOffices = self.$props.user.office_id;
      axios
        .get("/api/getOffices")
        .then((res) => {
          self.offices = res.data;
          if (self.$props.user.role_id == 1) {
            self.offices.unshift({ id: 0, name: "--выбор--" });
            self.filterOffices = self.offices[1].id;
          }
          if (self.$props.user.office_id > 0) {
            self.offices = self.offices.filter(
              (o) => o.id == self.$props.user.office_id
            );
          }
        })
        .catch((error) => console.log(error));
    },
    getGroup() {
      return _.filter(this.users, function (o) {
        return o.group_id == o.id;
      });
    },
    getUsers() {
      let self = this;
      if (self.$props.user.role_id == 3) {
        self.userid = self.$props.user.id;
        self.pieUser();
        return;
      }
      // let get = self.$props.user.role_id == 1 ? "/api/users" : "/api/getusers";
      let get = "/api/getusers";
      axios
        .get(get)
        .then((res) => {
          self.users = res.data.map(
            ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              group_id,
              order,
              statnew,
              pic,
              inp,
              cb,
              office_id,
              notint,
              noans,
              lang,
              trash,
            }) => ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              pic,
              group_id,
              order,
              statnew,
              inp,
              cb,
              office_id,
              notint,
              noans,
              lang,
              trash,
            })
          );
          if (self.$props.user.role_id == 1 && self.filterOffices > 0) {
            self.users = self.users.filter(
              (f) => f.office_id == self.filterOffices
            );
          }
          if (self.$props.user.role_id != 1) {
            self.users = self.users.filter(
              (f) => f.group_id == self.$props.user.group_id
            );
          }
          self.group = self.getGroup();
        })
        .catch((error) => console.log(error));
    },
  },
};
</script>

<style>
.scroll-y {
  max-height: 60vh;
  overflow: auto;
}
</style>
