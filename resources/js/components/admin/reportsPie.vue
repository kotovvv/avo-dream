<template>
  <div>
    <v-tabs v-model="tab" background-color="primary" dark>
      <v-tab> Менеджеры </v-tab>
      <v-tab> Поставщики </v-tab>
      <v-tab> Телефония </v-tab>
      <v-tab> Отчёт менеджера </v-tab>
      <v-tab> Профит </v-tab>
    </v-tabs>
    <v-tabs-items v-model="tab">
      <v-tab-item>
        <reportUserPie :user="$attrs.user"></reportUserPie>
      </v-tab-item>
      <v-tab-item>
        <reportProviderPie :user="$attrs.user"></reportProviderPie>
      </v-tab-item>
      <v-tab-item>
        <reportOperators />
      </v-tab-item>
      <v-tab-item>
        <reportManagerCalls />
      </v-tab-item>
      <v-tab-item>
        <reportProfit />
      </v-tab-item>
    </v-tabs-items>
  </div>
</template>

<script>
import reportProviderPie from "./reportProviderPie";
import reportUserPie from "./reportUserPie";
import reportOperators from "./reportOperators";
import reportManagerCalls from "./reportManagerCalls";
import reportProfit from "./reportProfit";
export default {
  name: "ReportsPie",
  components: {
    reportProviderPie,
    reportUserPie,
    reportOperators,
    reportManagerCalls,
    reportProfit,
  },
  data: () => ({
    tab: 0,
  }),
};
</script>
