<template>
  <v-card class="mx-auto" max-width="500">
    <v-card-title>
      <v-toolbar flat>
        <v-toolbar-title>Поставщики</v-toolbar-title>
        <v-divider class="mx-4" inset vertical></v-divider>
        <v-spacer></v-spacer>
        <v-dialog v-model="dialog" max-width="500px">
          <template v-slot:activator="{ on, attrs }">
            <v-btn color="primary" dark v-bind="attrs" v-on="on">
              Добавить поставщика
            </v-btn>
            <v-btn @click="getProvider">
              <v-icon dark> mdi-refresh </v-icon></v-btn
            >
          </template>
          <v-card>
            <v-card-title>
              <span class="headline">{{ formTitle }}</span>
            </v-card-title>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="blue darken-1" text @click="close"> Отмена </v-btn>
              <v-btn color="blue darken-1" text @click="save">
                Сохранить
              </v-btn>
            </v-card-actions>
            <v-card-text>
              <v-container>
                <v-row>
                  <v-col cols="6">
                    <v-text-field
                      v-model="editedItem.name"
                      label="Наименование"
                    ></v-text-field>
                  </v-col>
                  <v-text-field
                    v-model="editedItem.password"
                    label="Пароль"
                  ></v-text-field>
                  <!-- <v-col cols="6">
                    <v-switch
                      v-model="editedItem.active"
                      label="Показывать:"
                    ></v-switch>
                  </v-col> -->
                  <v-col cols="12">
                    <v-select
                      multiple
                      :items="users"
                      v-model="editedItem.related_users_id"
                      item-text="name"
                      item-value="id"
                      label="Связанные пользователи"
                    ></v-select>
                  </v-col>

                  <v-col cols="12">
                    <v-select
                      :items="users"
                      v-model="editedItem.user_id"
                      item-text="name"
                      item-value="id"
                      label="Пользователь для импорта"
                    ></v-select>
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-if="
                        editedItem.group == 'Свободные' ||
                        (editedItem.related_provider_ids &&
                          editedItem.related_provider_ids.length > 0)
                      "
                      v-model="editedItem.related_provider_ids"
                      :items="
                        providers.filter((f) => {
                          return (
                            f.id != editedItem.id && !nofree.includes(f.id)
                          );
                        })
                      "
                      item-text="name"
                      item-value="id"
                      label="Baers"
                      outlined
                      multiple
                    ></v-select>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      outlined
                      label="baer1;baer2;baer3"
                      v-model="editedItem.baer"
                      value="editedItem.baer"
                    ></v-textarea>
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="editedItem.tel"
                      label="ApiKey"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="6">
                    <v-select
                      multiple
                      :items="offices"
                      v-model="editedItem.office_id"
                      item-text="name"
                      item-value="id"
                      label="Office"
                    ></v-select>
                  </v-col>
                </v-row>
              </v-container>
            </v-card-text>
          </v-card>
        </v-dialog>
        <v-dialog v-model="dialogDelete" max-width="500px">
          <v-card>
            <v-card-title class="headline">Удалить поставщика?</v-card-title>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="blue darken-1" text @click="closeDelete">Нет</v-btn>
              <v-btn color="blue darken-1" text @click="deleteItemConfirm"
                >Да</v-btn
              >
              <v-spacer></v-spacer>
            </v-card-actions>
          </v-card>
        </v-dialog>
      </v-toolbar>
    </v-card-title>
    <v-data-table
      :headers="headers"
      :items="providers"
      sort-by="role_id"
      class="elevation-1"
      group-by="group"
      show-group-by
      :items-per-page="100"
      height="70vh"
      :expanded.sync="expanded"
    >
      <template v-slot:group.header="{ group, headers, toggle, isOpen }">
        <td :colspan="headers.length">
          <v-btn @click="toggle" small icon :ref="group" :data-open="isOpen">
            <v-icon v-if="isOpen">mdi-chevron-up</v-icon>
            <v-icon v-else>mdi-chevron-down</v-icon>
          </v-btn>
          {{ group }}
        </td>
      </template>
      <template v-slot:expanded-item="{ headers, item }">
        <td :colspan="headers.length">
          <div class="row">
            <div class="col">
              <h6>Details</h6>
              ... {{ item.name }}
            </div>
          </div>
        </td>
      </template>
      <template v-slot:item.actions="{ item }">
        <v-icon small class="mr-2" @click="editItem(item)"> mdi-pencil </v-icon>
        <v-icon
          small
          v-if="$attrs.user.role_id == 1 && $attrs.user.office_id == 0"
          @click="deleteItem(item)"
        >
          mdi-delete
        </v-icon>
      </template>
      <template v-slot:item.report="{ item }">
        <statusesProvider :provider="item" />
      </template>
      <!-- <template v-slot:item.report="{ item }">
      <v-icon small class="mr-2" @click="report(item)"> mdi-file-chart-outline </v-icon>
      <v-icon small @click="deleteItem(item)"> mdi-delete </v-icon>
    </template> -->
      <template v-slot:no-data>
        <v-btn color="primary" @click="getProvider"> Reset </v-btn>
      </template>
    </v-data-table>
  </v-card>
</template>

<script>
import statusesProvider from "./statusProvider";
import axios from "axios";
export default {
  data: () => ({
    provider: {},
    dialog: false,
    dialogDelete: false,
    providers: [],
    users: [],
    headers: [
      { text: "Наименование", value: "name", groupable: false },

      {
        text: "Редактировать",
        value: "actions",
        sortable: false,
        groupable: false,
      },
      { text: "Отчёт", value: "report", sortable: false, groupable: false },
      { text: "Категория", value: "group", align: "right" },
    ],

    editedIndex: -1,
    editedItem: {
      name: "",
      password: "",
      active: 1,
      related_users_id: [],
      related_provider_ids: [],
      office_id: [],
      user_id: 0,
    },
    defaultItem: {
      name: "",
      password: "",
      active: 1,
      related_users_id: [],
      related_provider_ids: [],
      office_id: [],
      user_id: 0,
    },
    offices: [],
    expanded: [],
    togglers: {},
  }),

  computed: {
    formTitle() {
      return this.editedIndex === -1
        ? "Новый поставщик"
        : "Редактировать поставщика";
    },
    nofree() {
      let nf = [];
      this.providers.map((p) => {
        if (
          p.related_provider_ids &&
          p.related_provider_ids.length > 0 &&
          p.id != this.editedItem.id
        ) {
          nf.push(p.id);

          if (Array.isArray(p.related_provider_ids)) {
            p.related_provider_ids.forEach((e) => {
              nf.push(e);
            });
          }
        }
      });
      return nf;
    },
  },

  watch: {
    dialog(val) {
      val || this.close();
    },
    dialogDelete(val) {
      val || this.closeDelete();
    },
  },

  mounted() {
    // this.initialize(),
    this.getOffices();
    this.getUsers();
    this.getProvider();
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
    getOffices() {
      let self = this;
      axios
        .get("/api/getOffices")
        .then((res) => {
          self.offices = res.data;
        })
        .catch((error) => console.log(error));
    },
    getUsers() {
      let self = this;
      axios
        .get("/api/users")
        .then((res) => {
          self.users = res.data;
        })
        .catch((error) => console.log(error));
    },
    report(item) {
      if (this.provider == item) {
        this.provider = {};
        return;
      }
      this.provider = item;
    },
    getProvider() {
      let self = this;
      axios
        .get("/api/providerall")
        .then((res) => {
          self.providers = res.data;
          self.providers = self.providers.map(function (p) {
            p.group = p.group ?? "Свободные";
            if (p.related_provider_ids && p.related_provider_ids.length > 0) {
              p.related_provider_ids = JSON.parse(p.related_provider_ids);
              p.group = p.name;
              if (Array.isArray(p.related_provider_ids)) {
                p.related_provider_ids.forEach((e) => {
                  self.providers[
                    self.providers.findIndex((pix) => pix.id == e)
                  ].group = p.name;
                });
              }
            }
            if (p.related_users_id.length > 0)
              p.related_users_id = JSON.parse(p.related_users_id);
            if (p.office_id.length > 0) p.office_id = JSON.parse(p.office_id);
            return p;
          });
          setTimeout(() => {
            // wait and then close all groups

            self.closeAll();
          }, 300);
        })
        .catch((error) => console.log(error));
    },
    saveProvider(provider) {
      let self = this;
      delete provider.group;
      axios
        .post("/api/provider", provider)
        .then((res) => {
          if (provider.id == undefined) {
            let idx = self.providers.indexOf(provider);
            Object.assign(self.providers[idx], res.data.provider);
          }
        })
        .catch((error) => console.log(error));
    },
    editItem(item) {
      this.editedIndex = this.providers.indexOf(item);
      this.editedItem = Object.assign({}, item);
      if (!Array.isArray(item.related_users_id))
        this.editedItem.related_users_id = [];
      if (!Array.isArray(item.office_id)) this.editedItem.office_id = [];
      this.dialog = true;
    },
    deleteItem(item) {
      this.editedIndex = this.providers.indexOf(item);
      this.editedItem = Object.assign({}, item);
      this.dialogDelete = true;
    },

    deleteItemConfirm() {
      axios
        .delete("/api/provider/" + this.editedItem.id)
        .then((res) => {
          // console.log(res);
        })
        .catch((error) => console.log(error));
      this.providers.splice(this.editedIndex, 1);
      this.closeDelete();
    },

    close() {
      this.dialog = false;
      this.$nextTick(() => {
        this.editedItem = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
      });
    },

    closeDelete() {
      this.dialogDelete = false;
      this.$nextTick(() => {
        this.editedItem = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
      });
    },

    save() {
      if (this.editedIndex > -1) {
        this.saveProvider(this.editedItem);
        Object.assign(this.providers[this.editedIndex], this.editedItem);
      } else {
        this.saveProvider(this.editedItem);
        this.providers.push(this.editedItem);
      }
      this.close();
    },
  },
  components: {
    statusesProvider,
  },
};
</script>
