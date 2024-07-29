<template>
  <v-theme-provider
    theme="dark"
    style="background-color: #1e2833; height: 98vh"
  >
    <div id="refund">
      <v-container fluid>
        <v-row>
          <v-col cols="5">
            <v-row>
              <v-btn-toggle v-model="btn_today">
                <v-btn @click="whichOnTime(0)">Сегодня</v-btn>
                <v-btn @click="whichOnTime(1)">Завтра</v-btn>
                <v-btn @click="whichOnTime()">Все</v-btn></v-btn-toggle
              >
            </v-row>
            <v-data-table
              id="ontimeTable"
              :headers="ontimeHeaders"
              item-key="id"
              :items="filterontime"
              ref="datatable"
              @click:row="clickrow"
              :footer-props="{
                'items-per-page-options': [],
                'items-per-page-text': '',
              }"
              style="background-color: #1e2833"
            >
              <template v-slot:item.date="{ item }">
                <h5 style="color: hsla(0, 0%, 100%, 0.7)">{{ item.date }}</h5>
              </template>
              <template v-slot:item.name="{ item }">
                <h5 style="color: hsla(0, 0%, 100%, 0.7)">{{ item.name }}</h5>
              </template>
              <template v-slot:item.tel="{ item }">
                <h5 style="color: hsla(0, 0%, 100%, 0.7)">{{ item.tel }}</h5>
              </template>
              <template v-slot:item.email="{ item }">
                <h5 style="color: hsla(0, 0%, 100%, 0.7)">{{ item.email }}</h5>
              </template>
            </v-data-table>
          </v-col>
          <v-col cols="7">
            <v-card
              rounded
              class="rounded-xl"
              style="background-color: #1e2833"
            >
              <v-card-title class="text-h5">
                <!-- @change="putSelectedLidsDB" -->

                <div class="wrp__statuses" v-if="lid.id">
                  <v-btn
                    v-for="status in statusesnonew"
                    :key="status.id"
                    :color="status.color"
                    style="color: hsla(0, 0%, 100%, 0.7)"
                    @click="
                      selectedStatus = status.id;
                      changeStatus();
                    "
                    :class="{ status_checked: lid.status_id == status.id }"
                    large
                    ><b>{{ status.name }}</b></v-btn
                  >
                </div>
              </v-card-title>
            </v-card>
            <div class="mt-3"></div>
            <v-card class="pa-3" theme="dark" style="background-color: #1e2833">
              <v-card-text>
                <v-row>
                  <v-col cols="6">
                    <div>
                      <v-btn class="btn" @click="toback" v-if="back.id"
                        >Назад</v-btn
                      >
                      <v-btn
                        plain
                        @click="
                          copyTo(lid.name + '\n' + lid.email + '\n' + lid.tel)
                        "
                        ><v-icon small>mdi-content-copy</v-icon></v-btn
                      >
                    </div>

                    <h5>
                      <b>Имя: {{ lid.name }}</b>
                      <v-btn plain @click="copyTo(lid.name)"
                        ><v-icon small>mdi-content-copy</v-icon></v-btn
                      >
                    </h5>
                    <h5>
                      <b>Email: {{ lid.email }}</b>
                      <v-btn plain @click="copyTo(lid.email)"
                        ><v-icon small>mdi-content-copy</v-icon></v-btn
                      >
                    </h5>
                    <h5>
                      <b>Tел:</b>
                      <template v-if="$attrs.user.sip == 0">
                        <a
                          class="tel"
                          :href="'sip:' + lid.tel"
                          style="color: hsla(0, 0%, 100%, 0.7)"
                          @click.stop="qtytel(lid.id)"
                        >
                          {{ lid.tel }}
                        </a>
                        <span
                          @click.prevent.stop="
                            qtytel(lid.id);
                            wp_call(lid);
                          "
                        >
                        </span>
                      </template>
                      <!-- <template v-else>
                        <span
                          class="tel"
                          @click.prevent.stop="
                            qtytel(lid.id);
                            wp_call(lid);
                          "
                        >
                          {{ lid.tel }}
                        </span>
                        <span>
                          <a
                            :href="'sip:' + lid.tel"
                            style="color: hsla(0, 0%, 100%, 0.7)"
                            @click.stop="qtytel(lid.id)"
                          >
                            <v-icon small> mdi-headset </v-icon>
                          </a>
                        </span>
                      </template> -->
                      <v-btn plain @click="copyTo(lid.tel)"
                        ><v-icon small>mdi-content-copy</v-icon></v-btn
                      >
                    </h5>
                    <h5>
                      <b>Поставщик: {{ lid.provider }}</b>
                    </h5>
                    <h5 class="mt-3">
                      <b>Афилятор: {{ lid.afilyator }}</b>
                    </h5>
                  </v-col>

                  <v-col cols="6">
                    Сообщение
                    <v-textarea
                      rows="3"
                      v-model="text_message"
                      :value="text_message"
                      solo
                    ></v-textarea>
                  </v-col>
                </v-row>

                <v-row>
                  <v-col>
                    <v-btn class="border" @click="getBTC">Отримати BTC</v-btn>
                    <span v-if="btc != ''" class="ml-2">
                      {{ btc }}
                      <v-btn plain @click="copyTo(btc)"
                        ><v-icon small>mdi-content-copy</v-icon></v-btn
                      ></span
                    >
                  </v-col>
                  <v-col v-if="selectedStatus == 9"
                    >Перезвон
                    <div class="border px-2">
                      <!-- @input="setTime" -->
                      <v-datetime-picker
                        v-model="set_time"
                        ref="datetime"
                        :time-picker-props="timeProps"
                        datetime="datetime"
                      >
                      </v-datetime-picker></div
                  ></v-col>
                  <v-col v-if="selectedStatus == 10">
                    Сумма депозита*
                    <v-text-field
                      required
                      v-model="depozit_val"
                      class="border px-2 mb-4"
                      @keypress="filter()"
                      @paste="paste"
                      prepend-inner-icon="mdi-currency-usd"
                    ></v-text-field
                  ></v-col>
                  <v-col v-if="selectedStatus == 20">
                    Сумма pending*
                    <v-text-field
                      required
                      v-model="pending_val"
                      class="border px-2 mb-4"
                      @keypress="filter()"
                      @paste="paste"
                      prepend-inner-icon="mdi-currency-usd"
                    ></v-text-field
                  ></v-col>
                  <v-col v-if="selectedStatus == 11">
                    Сумма trash*
                    <v-text-field
                      required
                      v-model="trash_val"
                      class="border px-2 mb-4"
                      @keypress="filter()"
                      @paste="paste"
                      prepend-inner-icon="mdi-currency-usd"
                    ></v-text-field
                  ></v-col>
                </v-row>
                <v-row>
                  <v-col cols="12">
                    <logtel :lid_id="lid.id" :key="lid.id" />
                  </v-col>
                </v-row>
              </v-card-text>

              <v-card-actions>
                <v-row>
                  <v-spacer></v-spacer>

                  <v-btn
                    v-if="[9, 10, 11, 20].includes(selectedStatus)"
                    color="dark primary"
                    large
                    class="btn"
                    :disabled="
                      (selectedStatus == 20 && pending_val < 1) ||
                      (selectedStatus == 11 && trash_val < 1) ||
                      (selectedStatus == 10 &&
                        depozit_val < 1 &&
                        text_message == '') ||
                      (this.selectedStatus == 9 && this.set_time === '')
                    "
                    @click.native.once="
                      putSelectedLidsDB();
                      dial = false;
                    "
                  >
                    Відправити
                  </v-btn>
                </v-row>
              </v-card-actions>
            </v-card>
          </v-col>

          <!-- <v-col cols="3">
          <v-text-field
            v-model="search"
            append-icon="mdi-magnify"
            label="Поиск"
            outlined
            rounded
            @click:append=""
          ></v-text-field>
          <v-select
            label="Фильтр"
            v-model="filterStatus"
            :items="statuses"
            item-text="name"
            item-value="id"
            outlined
            rounded
            multiple
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
          <v-data-table
            :search="search"
            id="logTable"
            :headers="logHeaders"
            item-key="id"
            :items="filterlogs"
            ref="datatable"
            @click:row="clickrow"
            :footer-props="{
              'items-per-page-options': [],
              'items-per-page-text': '',
            }"
          ></v-data-table>
        </v-col> -->
        </v-row>
      </v-container>
      <v-snackbar
        v-model="snackbar"
        top
        rigth
        timeout="6000"
        color="success"
        dark
      >
        {{ message }}
        <template v-slot:action="{ attrs }">
          <v-btn color="white" text v-bind="attrs" @click="snackbar = false">
            Х
          </v-btn>
        </template>
      </v-snackbar>
    </div>
  </v-theme-provider>
</template>
<script>
import axios from "axios";
import moment from "moment";
import logtel from "./logtel";
export default {
  name: "Wlids",
  components: {
    logtel,
  },
  data() {
    return {
      search: "",
      statuses: [],
      statusesnonew: [],
      // filterstatuses: [],
      filterStatus: [],
      selectedStatus: [],
      lid: {},
      logs: [],
      ontime: [],
      set_time: "",
      filterontime: [],
      providers: [],
      message: "",
      snackbar: false,
      pending_val: 0,
      datetime: "",
      timeProps: { format: "24hr" },
      text_message: "",
      depozit_val: "",
      pending_val: "",
      trash_val: "",
      ontimeHeaders: [
        { text: "Перезвон", value: "ontime" },
        { text: "Имя", value: "name" },
        { text: "Email", value: "email" },
        { text: "Телефон.", align: "start", value: "tel" },
        //{ text: "Афилятор", value: "afilyator" },
        // { text: "Поставщик", value: "provider" },
        //{ text: "Создан", value: "date_created" },
        // { text: "Статус", value: "status" },
        //{ text: "Pending", value: "pending" },
        //{ text: "Депозит", value: "deposit" },
        //{ text: "Сообщение", value: "text" },
        // { text: "Адрес", value: "address" },

        { text: "", value: "actions", sortable: false },
      ],
      logHeaders: [
        { text: "Имя", value: "name" },
        { text: "Email", value: "email" },
        { text: "Телефон.", align: "start", value: "tel" },
        { text: "Афилятор", value: "afilyator" },
        // { text: "Поставщик", value: "provider" },
        { text: "Создан", value: "date_created" },
        { text: "Статус", value: "status" },
        { text: "Перезвон", value: "date" },
        { text: "Pending", value: "pending" },
        { text: "Депозит", value: "deposit" },
        { text: "Trash", value: "firstprioritet" },
        { text: "Сообщение", value: "text" },
        { text: "Адрес", value: "address" },

        { text: "", value: "actions", sortable: false },
      ],
      btc: "",
      back: {},
      btn_today: 0,
    };
  },

  mounted() {
    this.getStatuses();
    this.getProviders();
    this.$vuetify.theme.dark = true;
  },
  watch: {
    datetime: function (newval, oldval) {
      if ((newval == null || newval != oldval) && this.lid.id != "") {
        this.setTime();
      }
    },
  },
  computed: {
    filterlogs() {
      return this.logs.filter((i) => {
        return (
          !this.filterStatus.length || this.filterStatus.includes(i.status_id)
        );
      });
    },
  },
  methods: {
    whichOnTime(ti = 3) {
      if (ti == 3) {
        this.filterontime = this.ontime;
        return;
      }
      const today = moment().format("YYYY-MM-DD");
      const tomorrow = moment().add(1, "day").format("YYYY-MM-DD");
      if (ti == 0) {
        this.filterontime = this.ontime.filter((t) => {
          return t.ontime && t.ontime.substring(0, 10) == today;
        });
      }
      if (ti == 1) {
        this.filterontime = this.ontime.filter((t) => {
          return t.ontime && t.ontime.substring(0, 10) == tomorrow;
        });
      }
    },
    getProviders() {
      let self = this;
      axios
        .get("/api/provider")
        .then((res) => {
          self.providers = res.data.map(({ name, id }) => ({ name, id }));
        })
        .catch((error) => console.log(error));
    },
    changeStatus() {
      if (![9, 10, 20, 11].includes(this.selectedStatus)) {
        this.putSelectedLidsDB();
      }
      return false;
    },
    setTime() {
      const self = this;
      let send = {};
      if (
        this.$refs.datetime == undefined ||
        this.$refs.datetime.formattedDatetime == ""
      )
        return;
      send.ontime = this.$refs.datetime.formattedDatetime;
      send.id = this.lid.id;

      axios
        .post("api/Lid/ontime", send)
        .then(function (response) {
          self.$refs.datetime.clearHandler();
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    clickrow(i, row) {
      this.back = this.lid;
      this.lid = i;
      this.selectedStatus = this.lid.status_id;
      this.btc = "";
    },
    toback() {
      this.lid = this.back;
      this.selectedStatus = this.lid.status_id;
      this.btc = "";
      this.back = {};
    },
    getUserLids() {
      const self = this;
      self.btc = "";
      axios
        .get("/api/getUserLids/" + self.$attrs.user.id)
        .then((res) => {
          self.lid = res.data.lid;
          self.lid.date = self.lid.updated_at.substring(0, 10);
          self.lid.status =
            self.statuses.find((s) => s.id == self.lid.status_id).name || "";
          self.lid.provider =
            self.providers.find((p) => p.id == self.lid.provider_id).name || "";
          self.selectedStatus = self.lid.status_id;
          // self.logs = res.data.logs;
          // self.logs.map(function (t) {
          //   t.status = self.statuses.find((f) => {
          //     return f.id == t.status_id;
          //   }).name;
          //   t.provider = self.providers.find((p) => {
          //     return p.id == p.provider_id;
          //   }).name;
          //   t.date = t.ontime ? t.ontime.substring(0, 10) : "";

          //   t.date_created = t.created_at.substring(0, 10);
          // });

          self.ontime = res.data.ontime;
          if (self.ontime && self.ontime.length > 0) {
            const today = new Date();
            const curdate =
              today.getFullYear() +
              "-" +
              ("0" + (today.getMonth() + 1)).slice(-2) +
              "-" +
              ("0" + today.getDate()).slice(-2);
            self.ontime.map(function (t) {
              const date_ontime =
                t.ontime && t.ontime.length > 10
                  ? t.ontime.substring(0, 10)
                  : "";

              // if (t.ontime && t.ontime.length > 10 && curdate == date_ontime) {
              //   t.date = new Date(t.ontime)
              //     .toLocaleTimeString()
              //     .substring(0, 5);
              // } else {
              //   t.date = t.ontime ?? "";
              // }
              // t.provider = self.providers.find((p) => {
              //   return p.id == t.provider_id;
              // }).name;
              // t.status = self.statuses.find((f) => {
              //   return f.id == t.status_id;
              // }).name;
              t.date_created = t.created_at.substring(0, 10);
            });
          }
          setTimeout(() => {
            self.whichOnTime(0);
          }, 300);
        })
        .catch((error) => console.log(error));
    },
    getStatuses() {
      let self = this;
      axios
        .get("/api/statuses")
        .then((res) => {
          self.statuses = res.data.map(({ name, id, color }) => ({
            name,
            id,
            color,
          }));
          self.statusesnonew = self.statuses.filter(
            (e) => e.id != 8 && e.id != 22
          );
          self.statuses = self.statuses.map((e) => e);
          self.getUserLids();
        })
        .catch((error) => console.log(error));
    },
    qtytel(id) {
      let data = {};
      data.lid_id = id;
      data.user_id = this.$attrs.user.id;
      axios
        .post("/api/qtytel", data)
        .then((res) => {
          // self.lids.find((i) => i.id == data.lid_id).qtytel = res.data;
        })
        .catch((error) => console.log(error));
    },
    copyTo(address) {
      this.message = "Copied to clipboard";
      this.snackbar = true;
      if (address == "") {
        this.message = "Нема вільних адресів";
        return;
      }
      this.changeDateBTC(address);
      if (navigator.clipboard && window.isSecureContext) {
        // navigator clipboard api method'
        return navigator.clipboard.writeText(address);
      } else {
        // text area method
        let textArea = document.createElement("textarea");
        textArea.value = address;
        // make the textarea out of viewport
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        return new Promise((res, rej) => {
          // here the magic happens
          document.execCommand("copy") ? res() : rej();
          // document.execCommand("copy") ? res() : rej(),window.prompt("Copy to clipboard: Ctrl+C, Enter", address);
          textArea.remove();
        });
      }
    },
    changeDateBTC(address) {
      let data = {};
      data.address = address;
      axios
        .post("/api/changeDateBTC", data)
        .catch((error) => console.log(error));
    },
    wp_call(item) {
      this.copyTo(item.tel);
      if (this.webphone && !this.webphone.closed) {
        const tel = this.selectedServer.prefix.toString() + item.tel;
        this.webphone.webphone_api.call(tel);
        this.webphone.focus();
      } else {
        this.webphone = window.open(
          `/webphone/softphone.html?wp_serveraddress=${encodeURIComponent(
            this.selectedServer.server
          )}&wp_username=${encodeURIComponent(
            this.selectedServer.login
          )}&wp_password=${encodeURIComponent(
            this.selectedServer.password
          )}&wp_callto=${this.selectedServer.prefix.toString() + item.tel}`,
          "softphone",
          "width=400,height=540"
        );
      }
    },
    getBTC() {
      const self = this;
      let data = {};
      data.id = this.lid.id;
      data.user_id = this.lid.user_id;
      data.tel = this.lid.tel;
      //get new BTC from table for lead
      axios
        .post("/api/getBTC", data)
        .then((res) => {
          if (res.data.address == undefined) {
            self.copyTo("");
            return;
          }
          self.btc = res.data.address;
          self.copyTo(res.data.address);
        })
        .catch((error) => console.log(error));
    },
    filter: function (evt) {
      evt = evt ? evt : window.event;
      let expect = evt.target.value.toString() + evt.key.toString();

      if (!/^[-+]?[0-9]*\.?[0-9]*$/.test(expect)) {
        evt.preventDefault();
      } else {
        return true;
      }
    },
    paste(e) {
      if (e.type === "paste") {
        const clip = e.clipboardData.getData("Text");
        setTimeout(function () {
          e.target.value = clip.replace(/[^0-9]/g, "");
        });
      }
    },
    putSelectedLidsDB() {
      const self = this;
      let send = {};
      let send_el = {};

      send.id = self.lid.id;
      send_el.id = self.lid.id;
      send_el.tel = self.lid.tel;
      send_el.text = self.text_message;
      if (self.depozit_val > 0) {
        send_el.text = self.depozit_val + ": " + send_el.text;
      }
      if (self.pending_val > 0) {
        send_el.text = self.pending_val + ": " + send_el.text;
      }
      send_el.status_id = self.selectedStatus;
      send_el.user_id = self.lid.user_id;
      send.data = [];
      send.data.push(send_el);
      axios
        .post("api/Lid/updatelids", send)
        .then(function (response) {
          self.getUserLids();
          self.text_message = "";
        })
        .catch(function (error) {
          console.log(error);
        });
      self.setTime();
      if (self.depozit_val > 0) {
        self.setDepozit();
      }
      if (self.pending_val > 0) {
        self.setPending();
      }
      if (self.trash_val > 0) {
        self.setTrash();
      }
    },
    setTrash() {
      const self = this;
      let send = {};
      send.lid_id = this.lid.id;
      send.user_id = this.lid.user_id;
      send.trash = this.trash_val;
      axios
        .post("api/setTrash", send)
        .then(function (response) {
          self.trash_val = "";
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    setPending() {
      let self = this;
      let send = {};
      send.lid_id = this.lid.id;
      send.user_id = this.lid.user_id;
      send.pending = this.pending_val;
      axios
        .post("api/setPending", send)
        .then(function (response) {
          self.pending_val = "";
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    setDepozit() {
      let self = this;
      let send = {};
      send.lid_id = this.lid.id;
      send.user_id = this.lid.user_id;
      send.depozit = this.depozit_val;
      axios
        .post("api/setDepozit", send)
        .then(function (response) {
          self.depozit_val = "";
        })
        .catch(function (error) {
          console.log(error);
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.tel:hover {
  cursor: url(/img/support_agent.svg) 10 10, none;
  text-decoration: none;
}
.tel {
  display: inline-block;
  margin-right: 1rem;
  /*  color: #000; */
}
.tel.active {
  color: #7620df;
  font-weight: bold;
}
#maintable.v-data-table :deep() tr {
  outline: 2px solid transparent;
}

td .status_wrp {
  cursor: pointer;
}
#maintable.v-data-table :deep() tr.v-data-table__selected {
  border-bottom: transparent !important;
}
#maintable.v-data-table :deep() tr.v-data-table__expanded tr:hover {
  border: none;
}
#maintable :deep() .text-start {
  padding: 0 !important;
}
.blackborder {
  border-top: transparent !important;
}

.status_checked,
.wrp__statuses input:checked + label {
  border: 3px solid #7620df;
  border-radius: 30px;
}

.blackborder .row .col {
  margin-top: 1rem;
}
#maintable .v-data-table__wrapper tr td:last-child,
#ontime .v-data-table__wrapper tr td:last-child {
  width: 120px;
}
.fixwidth {
  width: 120px;
  height: 45px;
  overflow: hidden;
  display: block;
}
.hideStatus {
  display: none;
}
</style>


