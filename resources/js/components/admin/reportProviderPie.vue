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
                    pieProvider();
                    getProvidersForTime();
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
                    pieProvider();
                    getProvidersForTime();
                  "
                ></v-date-picker>
              </v-menu>
            </v-col>
          </v-row>
        </div>
      </v-col>
      <!-- <v-col
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
      </v-col> -->
      <v-col>
        <v-radio-group v-model="dep_reg" @change="pieProvider()" row>
          <v-radio label="Депозиторы" :value="1"></v-radio>
          <v-radio label="Регистраторы" :value="2"></v-radio>
          <v-radio label="Recovery" :value="3"></v-radio>
          <v-radio label="Все" :value="0"></v-radio>
        </v-radio-group>
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
        <v-row>
          <v-col cols="2" class="mt-3">
            <v-select
              v-model="selgeo"
              :items="geotel"
              item-text="name"
              item-value="id"
              label="Выбор стран"
              multiple
              outlined
              rounded
              @change="pieProvider()"
            ></v-select>
          </v-col>
        </v-row>
      </v-col>
      <v-col cols="4">
        <div class="pa-5 w-100 border wrp_users">
          <div class="my-3">Поиск провайдеров</div>
          <v-autocomplete
            v-model="selectedUser"
            :items="providers"
            label="Выбор"
            item-text="name"
            item-value="id"
            :return-object="true"
            append-icon="mdi-close"
            outlined
            rounded
          ></v-autocomplete>

          <div class="scroll-y">
            <v-list>
              <v-radio-group
                id="usersradiogroup"
                ref="radiogroup"
                v-model="userid"
                v-bind="providers"
                @change="pieProvider()"
              >
                <v-col v-for="user in providers" :key="user.id">
                  <v-radio
                    :label="user.name + ' (' + user.hm + ')'"
                    :value="user.id"
                    :disabled="disableuser == user.id"
                  >
                  </v-radio>
                </v-col>
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
            :headers="headers"
            item-key="id"
            :items="leads"
            ref="datatable"
            :footer-props="{
              'items-per-page-options': [50, 10, 100, 250, 500, -1],
              'items-per-page-text': '',
            }"
          >
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
export default {
  name: "ReportUserPie",
  components: {
    PieChart,
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
      providers: [],
      userid: null,
      disableuser: 0,
      offices: [],
      filterOffices: 1,
      akkvalue: [],
      group: [],
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
      dep_reg: 1,
      geotel: [
        { id: 421, name: "SK" },
        { id: 420, name: "CZ" },
        { id: 36, name: "HU" },
        { id: 48, name: "PL" },
        { id: 33, name: "FR" },
      ],
      selgeo: [],
    };
  },

  mounted() {
    // this.getUsers();
    this.getProvidersForTime();
    this.getOffices();
    this.getStatuses();
  },
  watch: {
    // selectedUser(user) {
    //   if (user == {}) {
    //     this.userid = null;
    //     this.akkvalue = [];
    //     return;
    //   }
    //   //this.disableuser = user.id;
    //   this.akkvalue = [];
    //   console.log(this.group);
    //   if (this.group) {
    //     this.akkvalue[user.office_id] = _.findIndex(
    //       this.group.filter((g) => g.office_id == user.office_id),
    //       {
    //         group_id: user.group_id,
    //       }
    //     );
    //   }
    // },
  },
  methods: {
    getProvidersForTime() {
      let self = this;
      axios
        .get(
          "/api/getProvidersForTime/" +
            self.dateTimeFrom +
            "/" +
            self.dateTimeTo
        )
        .then((res) => {
          self.providers = res.data.map(({ name, id, hm }) => ({
            name,
            id,
            hm,
          }));
          // self.providers.unshift({ name: "выбор", id: 0 });
          // self.getLidsOnDate();
        })
        .catch((error) => console.log(error));
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
        })
        .catch((error) => console.log(error));
    },
    pieProvider() {
      const self = this;
      self.loading = true;
      let data = {};
      data.userid = self.userid;
      data.dateTimeFrom = self.dateTimeFrom;
      data.dateTimeTo = self.dateTimeTo;
      data.dep_reg = self.dep_reg;
      if (self.selgeo.length) {
        data.geotel = self.selgeo;
      }
      axios
        .post("/api/pieProvider", data)
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
          self.loading = false;
        })
        .catch(function (error) {
          console.log(error);
        });
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
