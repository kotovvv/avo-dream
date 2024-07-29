<template>
  <div>
    <v-tabs v-model="tab" background-color="primary" dark>
      <v-tab> Пользователи/Офисы </v-tab>
      <v-tab> Статусы </v-tab>
      <v-tab> Поставщики </v-tab>
    </v-tabs>
    <v-tabs-items v-model="tab">
      <v-tab-item> <Users :user="$attrs.user" /></v-tab-item>
      <v-tab-item> <Statuses :user="$attrs.user" /></v-tab-item>
      <v-tab-item> <Providers :user="$attrs.user" /></v-tab-item>
    </v-tabs-items>
  </div>
</template>

<script>
import XLSX from "xlsx";
import axios from "axios";

export default {
  components: {
    Users: () => import("./users"),
    Statuses: () => import("./statusLid"),
    Providers: () => import("./providers"),
  },
  data: () => ({
    tab: 0,
  }),
};
</script>
