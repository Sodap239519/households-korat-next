<template>
  <div class="min-h-screen bg-gradient-to-br from-violet-50 via-fuchsia-50 to-purple-100 relative overflow-hidden">
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-violet-300/30 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-[28rem] h-[28rem] rounded-full bg-fuchsia-300/30 blur-3xl pointer-events-none"></div>

    <!-- Top bar -->
    <header class="relative h-14 sm:h-16 flex items-center justify-between px-3 sm:px-6 bg-white/70 backdrop-blur-xl border-b border-violet-200/50 z-10 gap-2">
      <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
        <div class="flex-shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-600 text-white flex items-center justify-center shadow">
          <i class="fi fi-rr-house-blank"></i>
        </div>
        <div class="min-w-0">
          <h1 class="text-sm sm:text-base font-bold text-slate-800 truncate">Households KORAT</h1>
          <p class="text-[10px] sm:text-[11px] text-slate-500 truncate hidden sm:block">ระบบวิเคราะห์คุณสมบัติครัวเรือนเปราะบาง · นครราชสีมา</p>
        </div>
      </div>
      <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
        <!-- Mobile: icon-only -->
        <Button
          icon="fi fi-rr-user-add"
          severity="secondary"
          outlined
          rounded
          class="sm:!hidden"
          @click="$router.push('/app/register')"
          v-tooltip.bottom="'สมัครสมาชิก'"
        />
        <Button
          icon="fi fi-rr-sign-in-alt"
          rounded
          class="sm:!hidden"
          @click="$router.push('/app/login')"
          v-tooltip.bottom="'เข้าสู่ระบบ'"
        />
        <!-- Desktop: full label -->
        <Button
          label="สมัครสมาชิก"
          icon="fi fi-rr-user-add"
          severity="secondary"
          outlined
          class="!hidden sm:!inline-flex whitespace-nowrap"
          @click="$router.push('/app/register')"
        />
        <Button
          label="เข้าสู่ระบบ"
          icon="fi fi-rr-sign-in-alt"
          iconPos="right"
          class="!hidden sm:!inline-flex whitespace-nowrap"
          @click="$router.push('/app/login')"
        />
      </div>
    </header>

    <main class="relative p-3 sm:p-6 max-w-7xl mx-auto space-y-4 sm:space-y-5">
      <!-- Hero -->
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 text-white p-4 sm:p-6 shadow-lg shadow-violet-500/30">
        <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <div class="relative flex items-start sm:items-center justify-between flex-wrap gap-3 sm:gap-4">
          <div class="min-w-0 flex-1">
            <p class="text-violet-100 text-xs sm:text-sm">ภาพรวมข้อมูล Households KORAT</p>
            <h2 class="text-lg sm:text-2xl font-bold mt-1">Households KORAT</h2>
            <p class="text-violet-100 text-xs sm:text-sm mt-2 leading-relaxed">
              ภาพรวมการดำเนินโครงการการพัฒนาและยกระดับเศรษฐกิจฐานราก
              เพื่อแก้ไขปัญหาความยากจนด้วยตลาดนำ เทคโนโลยี และนวัตกรรมเสริม
              <span class="font-semibold">จังหวัดนครราชสีมา</span>
            </p>
          </div>
          <div class="flex items-center gap-2 bg-white/15 backdrop-blur rounded-lg pl-3 pr-1 py-1 whitespace-nowrap">
            <i class="fi fi-rr-calendar text-white"></i>
            <Select
              v-model="selectedYear"
              :options="yearOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="ทุกปี"
              showClear
              :pt="{
                root:        { class: 'bg-transparent border-0 shadow-none text-white' },
                label:       { class: 'text-white text-xs sm:text-sm py-1 pr-1 pl-1' },
                dropdown:    { class: 'text-white' },
                clearIcon:   { class: 'text-white/80' },
                trigger:     { class: 'text-white' },
              }"
            />
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-16 text-violet-400">
        <i class="fi fi-rr-loading text-4xl animate-spin"></i>
      </div>

      <!-- Tabs -->
      <div v-else class="box-card p-2">
        <Tabs v-model:value="activeTab">
          <TabList>
            <Tab value="0"><span class="flex items-center gap-2"><i class="fi fi-rr-house-blank"></i> ภาพรวมครัวเรือน</span></Tab>
            <Tab value="1"><span class="flex items-center gap-2"><i class="fi fi-rr-mushroom"></i> การเพาะเห็ด</span></Tab>
            <Tab value="2"><span class="flex items-center gap-2"><i class="fi fi-rr-search"></i> การติดตาม</span></Tab>
            <Tab value="3"><span class="flex items-center gap-2"><i class="fi fi-rr-shop"></i> การตลาด</span></Tab>
            <Tab value="4"><span class="flex items-center gap-2"><i class="fi fi-rr-chart-mixed-up-circle-dollar"></i> เปรียบเทียบรายได้</span></Tab>
            <RouterLink to="/shop" class="flex items-center gap-2 px-4 py-2 ml-auto rounded-full bg-gradient-to-r from-orange-400 to-fuchsia-500 text-white text-sm font-semibold shadow-sm hover:shadow-md hover:brightness-105 transition self-center shrink-0">
              <i class="fi fi-rr-bag-shopping-filled"></i> เว็บไซต์ขายของ
            </RouterLink>
          </TabList>

          <TabPanels>
            <!-- TAB 1: ภาพรวมครัวเรือน -->
            <TabPanel value="0">
              <div class="space-y-5 pt-2">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                  <StatCard label="อำเภอทั้งหมด"  :value="o.summary?.total_districts"     icon="fi fi-rr-marker"      tone="violet" small />
                  <StatCard label="ตำบลทั้งหมด"  :value="o.summary?.total_subdistricts" icon="fi fi-rr-map-marker"  tone="indigo" small />
                  <StatCard label="ครัวเรือน"    :value="o.summary?.total_households"   icon="fi fi-rr-house-blank" tone="amber"  small />
                  <StatCard label="ผ่านเกณฑ์"    :value="o.summary?.passed"             icon="fi fi-rr-check"       tone="emerald" small />
                  <StatCard label="ไม่ผ่าน"     :value="o.summary?.failed"             icon="fi fi-rr-cross"       tone="rose"   small />
                  <StatCard label="คะแนนเฉลี่ย" :value="o.summary?.avg_total_score?.toFixed(2)" icon="fi fi-rr-chart-line-up" tone="cyan" small />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                  <div class="box-card p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                      <i class="fi fi-rr-chart-histogram text-violet-600"></i> Priority Distribution
                    </h3>
                    <AppChart type="bar"
                      :labels="priorityChart?.labels || []"
                      :series="priorityChart?.series || []"
                      :colors="priorityChart?.colors || []"
                      :legend="false"
                      :height="260" />
                  </div>
                  <div class="box-card p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                      <i class="fi fi-rr-chart-line-up text-blue-600"></i> รายได้/รายจ่าย/หนี้สิน รายอำเภอ
                    </h3>
                    <AppChart type="area"
                      :labels="incomeChart?.labels || []"
                      :series="incomeChart?.series || []"
                      :colors="['#10b981','#f59e0b','#ef4444']"
                      :height="260" />
                  </div>
                </div>

                <div class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <i class="fi fi-rr-chart-histogram text-fuchsia-600"></i> Priority รายอำเภอ
                  </h3>
                  <AppChart type="stacked-bar"
                    :labels="priorityByDistrictChart?.labels || []"
                    :series="priorityByDistrictChart?.series || []"
                    :colors="['#1e293b','#38bdf8','#fcd34d','#f9a8d4']"
                    :height="320" />
                </div>
              </div>
            </TabPanel>

            <!-- TAB 2: การเพาะเห็ด -->
            <TabPanel value="1">
              <div class="space-y-5 pt-2">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                  <HeroCard label="โควต้าทั้งหมด" :value="fmt(m.summary?.total_bags_quota)"  unit="ก้อน" icon="fi fi-rr-shopping-bag"   gradient="from-violet-500 via-purple-600 to-fuchsia-600" />
                  <HeroCard label="จัดสรรแล้ว"   :value="fmt(m.summary?.total_bags_allocated)" unit="ก้อน" icon="fi fi-rr-seedling"      gradient="from-fuchsia-500 via-pink-500 to-rose-500" :hint="allocPct" />
                  <HeroCard label="ผลผลิตรวม"   :value="fmt(m.summary?.total_harvest_kg, 2)" unit="กก." icon="fi fi-rr-mushroom"      gradient="from-emerald-500 via-teal-500 to-cyan-500" />
                  <HeroCard label="รายได้รวม"   :value="fmt(m.summary?.total_revenue, 2)"   unit="บาท" icon="fi fi-rr-money-bill-wave" gradient="from-amber-500 via-orange-500 to-rose-500" />
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                  <StatCard label="โควต้าอำเภอ" :value="m.summary?.total_quotas"      icon="fi fi-rr-clipboard-list" tone="violet"  small />
                  <StatCard label="การจัดสรร"   :value="m.summary?.total_allocations" icon="fi fi-rr-seedling"       tone="fuchsia" small />
                  <StatCard label="ติดตามผล"    :value="m.summary?.total_followups"   icon="fi fi-rr-list-check"     tone="purple"  small />
                  <StatCard label="ขายได้ (กก.)" :value="fmt(m.summary?.total_sold_kg, 2)" icon="fi fi-rr-shop" tone="orange" small />
                  <StatCard label="คงเหลือ (ก้อน)" :value="fmt((m.summary?.total_bags_quota || 0) - (m.summary?.total_bags_allocated || 0))" icon="fi fi-rr-box-alt" tone="cyan" small />
                  <StatCard label="ใช้โควต้า %" :value="allocPct" icon="fi fi-rr-pie-chart" tone="indigo" small />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                  <div class="box-card p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                      <i class="fi fi-rr-chart-histogram text-violet-600"></i> โควต้า vs จัดสรร (รายอำเภอ)
                    </h3>
                    <AppChart type="line"
                      :labels="quotaChart?.labels || []"
                      :series="quotaChart?.series || []"
                      :colors="['#6366f1','#f59e0b']"
                      :height="320" />
                  </div>
                  <div class="box-card p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                      <i class="fi fi-rr-chart-line-up text-emerald-600"></i> สัดส่วนรายได้ (รายอำเภอ)
                    </h3>
                    <AppChart type="area"
                      :labels="districtChart?.labels || []"
                      :series="districtChart?.series || []"
                      :colors="['#10b981']"
                      :legend="false"
                      :height="320" />
                  </div>
                </div>
              </div>
            </TabPanel>

            <!-- TAB 3: การติดตาม -->
            <TabPanel value="2">
              <div class="space-y-5 pt-2">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                  <StatCard label="ครั้งติดตามรวม" :value="t.totals?.total_followups" icon="fi fi-rr-list-check" tone="violet" />
                  <StatCard label="เดือนที่มีข้อมูล" :value="t.totals?.months_with_data" icon="fi fi-rr-calendar" tone="indigo" />
                  <StatCard label="ช่องทางขาย" :value="t.byChannel?.length || 0" icon="fi fi-rr-truck-side" tone="fuchsia" />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                  <div class="box-card p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                      <i class="fi fi-rr-truck-side text-cyan-600"></i> การขายตามช่องทาง
                    </h3>
                    <AppChart type="line"
                      :labels="channelChart?.labels || []"
                      :series="channelChart?.series || []"
                      :colors="['#0ea5e9','#10b981']"
                      dual-axis
                      y-left-label="ครั้ง"
                      y-right-label="บาท"
                      :height="280" />
                  </div>
                  <div class="box-card p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                      <i class="fi fi-rr-chart-line-up text-fuchsia-600"></i> ผลผลิต/ขาย/รายได้ รายเดือน
                    </h3>
                    <AppChart type="line"
                      :labels="monthlyChart?.labels || []"
                      :series="monthlyChart?.series || []"
                      :colors="['#10b981','#f97316','#ec4899']"
                      dual-axis
                      y-left-label="กก."
                      y-right-label="บาท"
                      :height="280" />
                  </div>
                </div>
              </div>
            </TabPanel>

            <!-- TAB 4: การตลาด -->
            <TabPanel value="3">
              <div class="space-y-5 pt-2">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                  <StatCard label="วิสาหกิจที่ขาย" :value="mk.totals?.total_enterprise_count" icon="fi fi-rr-shop" tone="fuchsia" />
                  <StatCard label="รายได้รวม"     :value="fmt(mk.totals?.total_revenue, 2)"  icon="fi fi-rr-money-bill-wave" tone="amber" />
                  <StatCard label="รายได้จากวิสาหกิจ" :value="fmt(mk.totals?.enterprise_revenue, 2)" icon="fi fi-rr-handshake" tone="violet" />
                </div>

                <!-- TOP 3 channels -->
                <div v-if="topChannels.length" class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                    <i class="fi fi-rr-trophy text-amber-500"></i> ช่องทางการขายที่ทำรายได้สูงสุด · TOP 3
                  </h3>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div v-for="(c, idx) in topChannels" :key="c.sale_channel"
                      :class="['relative overflow-hidden rounded-2xl text-white p-5 shadow-lg bg-gradient-to-br',
                        idx === 0 ? 'from-amber-500 via-orange-500 to-rose-500'
                        : idx === 1 ? 'from-slate-400 via-slate-500 to-slate-600'
                        : 'from-orange-700 via-amber-700 to-yellow-700']">
                      <div class="absolute -top-8 -right-8 w-32 h-32 rounded-full bg-white/15 blur-2xl pointer-events-none"></div>
                      <div class="relative">
                        <div class="flex items-center justify-between">
                          <div class="flex items-center gap-2">
                            <span :class="['inline-flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm shadow',
                              idx === 0 ? 'bg-amber-50 text-amber-700'
                              : idx === 1 ? 'bg-slate-100 text-slate-700'
                              : 'bg-orange-100 text-orange-700']">
                              {{ idx + 1 }}
                            </span>
                            <span class="text-white/90 text-xs font-medium">อันดับ {{ idx + 1 }}</span>
                          </div>
                          <div class="w-9 h-9 rounded-lg bg-white/20 backdrop-blur flex items-center justify-center">
                            <i :class="channelIcon(c.sale_channel) + ' text-base'"></i>
                          </div>
                        </div>
                        <p class="text-xl font-bold mt-3 tracking-tight">{{ channelLabels[c.sale_channel] || c.sale_channel }}</p>
                        <p class="text-2xl font-extrabold mt-1">
                          {{ fmt(c.total_revenue, 0) }}<span class="text-sm font-semibold text-white/85 ml-1">บาท</span>
                        </p>
                        <div class="text-[11px] text-white/85 mt-1.5 flex items-center gap-2 flex-wrap">
                          <span><i class="fi fi-rr-list-check"></i> {{ fmt(c.count) }} ครั้ง</span>
                          <span v-if="c.total_sold_kg != null">· <i class="fi fi-rr-shop"></i> {{ fmt(c.total_sold_kg, 2) }} กก.</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <i class="fi fi-rr-trophy text-amber-500"></i> Top วิสาหกิจ ตามรายได้
                  </h3>
                  <AppChart type="bar"
                    :labels="enterpriseChart?.labels || []"
                    :series="enterpriseChart?.series || []"
                    :colors="enterpriseChart?.colors || []"
                    :legend="false"
                    :height="320" />
                </div>

                <div v-if="mk.byEnterprise?.length" class="box-card p-5">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="text-left text-slate-500 border-b border-violet-100">
                        <th class="px-3 py-2 font-medium">#</th>
                        <th class="px-3 py-2 font-medium">วิสาหกิจ</th>
                        <th class="px-3 py-2 font-medium text-right">ขาย (กก.)</th>
                        <th class="px-3 py-2 font-medium text-right">รายได้ (บาท)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(e, idx) in mk.byEnterprise.slice(0, 10)" :key="e.enterprise_name" class="border-b border-violet-50 hover:bg-violet-50/30">
                        <td class="px-3 py-2.5 text-slate-500">{{ idx + 1 }}</td>
                        <td class="px-3 py-2.5 font-medium text-slate-700">{{ e.enterprise_name }}</td>
                        <td class="px-3 py-2.5 text-right">{{ fmt(e.total_sold_kg, 2) }}</td>
                        <td class="px-3 py-2.5 text-right font-semibold text-emerald-700">{{ fmt(e.total_revenue, 2) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </TabPanel>

            <!-- TAB 5: เปรียบเทียบรายได้ -->
            <TabPanel value="4">
              <div class="space-y-5 pt-2">
                <!-- Hero summary -->
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 text-white p-5 shadow-lg shadow-emerald-500/30">
                  <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/10 blur-3xl"></div>
                  <div class="relative flex items-center justify-between flex-wrap gap-3">
                    <div>
                      <p class="text-emerald-100 text-xs flex items-center gap-1">
                        <i class="fi fi-rr-bullseye"></i> เป้าหมาย
                      </p>
                      <h3 class="text-2xl font-bold mt-0.5">รายได้เพิ่มขึ้น <span class="text-amber-200">≥ {{ ic.summary?.target_pct ?? 15 }}%</span></h3>
                      <p class="text-emerald-100 text-xs mt-1">เปรียบเทียบ "รายได้สำรวจ/เดือน" กับ "รายได้รวมจากการขายเห็ด"</p>
                    </div>
                    <div class="text-right">
                      <p class="text-emerald-100 text-xs">% เพิ่มภาพรวม</p>
                      <p :class="['text-3xl font-extrabold', overallPctClass]">
                        {{ ic.summary?.overall_increase_pct == null ? '-' : ic.summary.overall_increase_pct.toFixed(2) + '%' }}
                      </p>
                      <p class="text-emerald-100 text-[11px] mt-0.5">
                        จาก {{ fmt(ic.summary?.total_survey_income, 0) }} → {{ fmt(ic.summary?.total_sales_revenue, 0) }} บาท
                      </p>
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                  <StatCard label="ครัวเรือนทั้งหมด" :value="ic.summary?.total_households" icon="fi fi-rr-house-blank" tone="violet" small />
                  <StatCard label="ครัวเรือนที่มีรายได้จากขาย" :value="ic.summary?.with_sales" icon="fi fi-rr-shop" tone="cyan" small />
                  <StatCard label="ผ่านเกณฑ์ 15%" :value="ic.summary?.passed_target" icon="fi fi-rr-check" tone="emerald" small />
                  <StatCard label="ยังไม่ผ่าน" :value="ic.summary?.failed_target" icon="fi fi-rr-cross" tone="rose" small />
                </div>

                <!-- Charts -->
                <div v-if="incomeByDistrictChart" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                  <div class="box-card p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                      <i class="fi fi-rr-chart-histogram text-emerald-600"></i> รายได้สำรวจ vs ขายเห็ด (รายอำเภอ)
                    </h3>
                    <AppChart type="bar"
                      :labels="incomeByDistrictChart.labels"
                      :series="incomeByDistrictChart.series"
                      :colors="['#94a3b8','#10b981']"
                      :height="320" />
                  </div>
                  <div class="box-card p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                      <i class="fi fi-rr-chart-line-up text-amber-600"></i> % รายได้เพิ่ม (รายอำเภอ)
                    </h3>
                    <AppChart type="bar"
                      :labels="incomePctChart.labels"
                      :series="incomePctChart.series"
                      :colors="incomePctChart.colors"
                      :legend="false"
                      :height="320" />
                  </div>
                </div>

                <!-- District table -->
                <div v-if="ic.byDistrict?.length" class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                    <i class="fi fi-rr-marker text-violet-500"></i> สรุปรายอำเภอ
                  </h3>
                  <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                      <thead>
                        <tr class="text-left text-slate-500 border-b border-violet-100">
                          <th class="px-3 py-2 font-medium">อำเภอ</th>
                          <th class="px-3 py-2 font-medium text-center">ครัวเรือน</th>
                          <th class="px-3 py-2 font-medium text-center">ผ่านเป้า</th>
                          <th class="px-3 py-2 font-medium text-right">รายได้สำรวจ</th>
                          <th class="px-3 py-2 font-medium text-right">รายได้ขาย</th>
                          <th class="px-3 py-2 font-medium text-right">เพิ่ม/ลด</th>
                          <th class="px-3 py-2 font-medium text-right">% เพิ่ม</th>
                          <th class="px-3 py-2 font-medium text-center">สถานะ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="d in ic.byDistrict" :key="d.district" class="border-b border-violet-50 hover:bg-violet-50/30">
                          <td class="px-3 py-2 font-medium text-slate-700">{{ d.district }}</td>
                          <td class="px-3 py-2 text-center text-slate-600">{{ fmt(d.with_sales_count) }}/{{ fmt(d.households_count) }}</td>
                          <td class="px-3 py-2 text-center">
                            <span class="text-emerald-700 font-semibold">{{ fmt(d.passed_count) }}</span>
                            <span class="text-slate-400 text-xs"> /{{ fmt(d.with_sales_count) }}</span>
                          </td>
                          <td class="px-3 py-2 text-right text-slate-600">{{ fmt(d.total_survey_income, 0) }}</td>
                          <td class="px-3 py-2 text-right text-emerald-700 font-semibold">{{ fmt(d.total_sales_revenue, 0) }}</td>
                          <td :class="['px-3 py-2 text-right font-semibold', Number(d.increase_amount) >= 0 ? 'text-emerald-700' : 'text-rose-700']">
                            {{ Number(d.increase_amount) >= 0 ? '+' : '' }}{{ fmt(d.increase_amount, 0) }}
                          </td>
                          <td :class="['px-3 py-2 text-right font-bold', pctClass(d.increase_pct)]">
                            <span v-if="d.is_new_income">+∞%</span>
                            <span v-else>{{ d.increase_pct == null ? '-' : (d.increase_pct >= 0 ? '+' : '') + d.increase_pct.toFixed(2) + '%' }}</span>
                          </td>
                          <td class="px-3 py-2 text-center">
                            <StatusBadge
                              :status="d.passed_target ? 'success' : 'failed'"
                              :label="d.passed_target ? 'ผ่าน' : 'ยังไม่ผ่าน'" />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </TabPanel>
          </TabPanels>
        </Tabs>
      </div>

      <p class="text-center text-xs text-slate-400 pt-4">
        © {{ new Date().getFullYear() }} Households KORAT · ระบบสำหรับเจ้าหน้าที่
        <button @click="$router.push('/app/login')" class="text-violet-600 hover:underline ml-2">เข้าสู่ระบบเพื่อจัดการข้อมูล</button>
      </p>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, defineComponent, h } from 'vue'
import api from '../api/index.js'
import AppChart from '../components/AppChart.vue'
import StatusBadge from '../components/StatusBadge.vue'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip

const activeTab = ref('0')
const loading = ref(true)
const data = ref({})
const selectedYear = ref(null)
const yearOptions = ref([])

// Convenience refs into sections
const o  = computed(() => data.value.overview  || {})
const m  = computed(() => data.value.mushroom  || {})
const t  = computed(() => data.value.tracking  || {})
const mk = computed(() => data.value.marketing || {})

function fmt(v, dec = 0) {
  if (v == null) return '-'
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}

const allocPct = computed(() => {
  const q = Number(m.value.summary?.total_bags_quota)     || 0
  const a = Number(m.value.summary?.total_bags_allocated) || 0
  if (q === 0) return ''
  return Math.round((a / q) * 100) + '%'
})

// ---- Charts (AppChart-friendly shape) ----
const priorityChart = computed(() => {
  if (!o.value.priorityCounts?.length) return null
  const colors = { A: '#1e293b', B: '#38bdf8', C: '#fcd34d', D: '#f9a8d4' }
  return {
    labels: o.value.priorityCounts.map(p => `Priority ${p.priority}`),
    series: [{ name: 'จำนวน', data: o.value.priorityCounts.map(p => p.count) }],
    colors: o.value.priorityCounts.map(p => colors[p.priority] || '#a78bfa'),
  }
})
const incomeChart = computed(() => {
  if (!o.value.byDistrict?.length) return null
  return {
    labels: o.value.byDistrict.map(d => d.district),
    series: [
      { name: 'รายได้',  data: o.value.byDistrict.map(d => Number(d.total_income)) },
      { name: 'รายจ่าย', data: o.value.byDistrict.map(d => Number(d.total_expense)) },
      { name: 'หนี้สิน', data: o.value.byDistrict.map(d => Number(d.total_debt)) },
    ],
  }
})
const priorityByDistrictChart = computed(() => {
  if (!o.value.priorityByDistrict?.length) return null
  const districts = [...new Set(o.value.priorityByDistrict.map(r => r.district))]
  const priorities = ['A', 'B', 'C', 'D']
  return {
    labels: districts,
    series: priorities.map(p => ({
      name: `Priority ${p}`,
      data: districts.map(d => {
        const row = o.value.priorityByDistrict.find(x => x.district === d && x.priority === p)
        return row ? row.count : 0
      }),
    })),
  }
})

const quotaChart = computed(() => {
  if (!m.value.quotaVsAllocated?.length) return null
  return {
    labels: m.value.quotaVsAllocated.map(r => r.district),
    series: [
      { name: 'โควต้า (ก้อน)',     type: 'column', data: m.value.quotaVsAllocated.map(r => Number(r.quota_bags || 0)) },
      { name: 'จัดสรรแล้ว (ก้อน)', type: 'line',   data: m.value.quotaVsAllocated.map(r => Number(r.allocated_bags || 0)) },
    ],
  }
})
const districtChart = computed(() => {
  if (!m.value.byDistrict?.length) return null
  return {
    labels: m.value.byDistrict.map(r => r.district),
    series: [{ name: 'รายได้ (บาท)', data: m.value.byDistrict.map(r => Number(r.total_revenue || 0)) }],
  }
})

const channelLabels = { direct: 'ขายตรง', online: 'ออนไลน์', enterprise: 'วิสาหกิจ', market: 'ตลาด' }
const channelIcons = {
  direct: 'fi fi-rr-handshake', online: 'fi fi-rr-globe',
  enterprise: 'fi fi-rr-shop',  market: 'fi fi-rr-shopping-cart',
}
function channelIcon(c) { return channelIcons[c] || 'fi fi-rr-truck-side' }

const channelChart = computed(() => {
  if (!t.value.byChannel?.length) return null
  return {
    labels: t.value.byChannel.map(c => channelLabels[c.sale_channel] || c.sale_channel),
    series: [
      { name: 'จำนวนครั้ง', type: 'column', data: t.value.byChannel.map(c => Number(c.count || 0)) },
      { name: 'รายได้ (บาท)', type: 'line', data: t.value.byChannel.map(c => Number(c.total_revenue || 0)) },
    ],
  }
})
const monthlyChart = computed(() => {
  if (!t.value.monthly?.length) return null
  return {
    labels: t.value.monthly.map(r => r.month),
    series: [
      { name: 'ผลผลิต (กก.)', type: 'column', data: t.value.monthly.map(r => Number(r.total_harvest_kg)) },
      { name: 'ขาย (กก.)',     type: 'column', data: t.value.monthly.map(r => Number(r.total_sold_kg)) },
      { name: 'รายได้ (บาท)',  type: 'line',   data: t.value.monthly.map(r => Number(r.total_revenue)) },
    ],
  }
})
const topChannels = computed(() => {
  if (!t.value.byChannel?.length) return []
  return [...t.value.byChannel]
    .sort((a, b) => Number(b.total_revenue || 0) - Number(a.total_revenue || 0))
    .slice(0, 3)
})

const enterpriseChart = computed(() => {
  if (!mk.value.byEnterprise?.length) return null
  const palette = ['#f59e0b','#f97316','#fb923c','#fbbf24','#fcd34d','#facc15','#eab308','#ca8a04','#a16207','#854d0e']
  return {
    labels: mk.value.byEnterprise.map(e => e.enterprise_name),
    series: [{ name: 'รายได้ (บาท)', data: mk.value.byEnterprise.map(e => Number(e.total_revenue || 0)) }],
    colors: mk.value.byEnterprise.map((_, i) => palette[i % palette.length]),
  }
})

// Income comparison (sync with Backend Dashboard)
const ic = computed(() => data.value.incomeComparison || {})
const overallPctClass = computed(() => {
  const p = ic.value.summary?.overall_increase_pct
  if (p == null) return 'text-white'
  if (p >= 15) return 'text-emerald-200'
  if (p >= 0)  return 'text-amber-200'
  return 'text-rose-200'
})
function pctClass(p) {
  if (p == null) return 'text-slate-400'
  if (p >= 15)   return 'text-emerald-700'
  if (p >= 0)    return 'text-amber-700'
  return 'text-rose-700'
}
const incomeByDistrictChart = computed(() => {
  if (!ic.value.byDistrict?.length) return null
  return {
    labels: ic.value.byDistrict.map(d => d.district),
    series: [
      { name: 'รายได้สำรวจ',  data: ic.value.byDistrict.map(d => Number(d.total_survey_income || 0)) },
      { name: 'รายได้ขายเห็ด', data: ic.value.byDistrict.map(d => Number(d.total_sales_revenue || 0)) },
    ],
  }
})
const incomePctChart = computed(() => {
  const list = ic.value.byDistrict || []
  if (!list.length) return { labels: [], series: [], colors: [] }
  return {
    labels: list.map(d => d.district),
    series: [{ name: '% เพิ่มขึ้น', data: list.map(d => d.increase_pct == null ? 0 : Number(d.increase_pct)) }],
    colors: list.map(d => {
      const p = d.increase_pct
      if (p == null) return '#94a3b8'
      if (p >= 15)   return '#10b981'
      if (p >= 0)    return '#f59e0b'
      return '#ef4444'
    }),
  }
})

// ---- Stat card / Hero card ----
const TONES = {
  violet: 'from-violet-500 to-violet-600',  indigo: 'from-indigo-500 to-indigo-600',
  fuchsia:'from-fuchsia-500 to-fuchsia-600', purple:'from-purple-500 to-purple-600',
  emerald:'from-emerald-500 to-emerald-600', amber: 'from-amber-500 to-amber-600',
  rose:   'from-rose-500 to-rose-600',       cyan:  'from-cyan-500 to-cyan-600',
  orange: 'from-orange-500 to-orange-600',
}
const StatCard = defineComponent({
  props: ['label', 'value', 'icon', 'tone', 'small'],
  setup(p) {
    return () => h('div', {
      class: `box-card group relative overflow-hidden ${p.small ? 'p-4' : 'p-5'}`,
    }, [
      h('div', { class: `absolute -top-8 -right-8 w-24 h-24 rounded-full bg-gradient-to-br ${TONES[p.tone] || TONES.violet} opacity-10 group-hover:opacity-20 transition` }),
      h('div', { class: 'relative flex items-start justify-between gap-2' }, [
        h('div', { class: 'min-w-0' }, [
          h('p', { class: 'text-[11px] text-slate-500 font-medium' }, p.label),
          h('p', { class: `mt-1 ${p.small ? 'text-lg' : 'text-2xl'} font-bold text-slate-800 truncate` }, p.value ?? '-'),
        ]),
        h('div', { class: `flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br ${TONES[p.tone] || TONES.violet} text-white flex items-center justify-center shadow-md` }, [
          h('i', { class: `${p.icon} text-base` }),
        ]),
      ]),
    ])
  },
})
const HeroCard = defineComponent({
  props: ['label', 'value', 'unit', 'icon', 'gradient', 'hint'],
  setup(p) {
    return () => h('div', { class: `relative overflow-hidden rounded-2xl bg-gradient-to-br ${p.gradient} text-white p-5 shadow-lg` }, [
      h('div', { class: 'absolute -top-8 -right-8 w-32 h-32 rounded-full bg-white/15 blur-2xl pointer-events-none' }),
      h('div', { class: 'relative' }, [
        h('div', { class: 'flex items-center justify-between' }, [
          h('p', { class: 'text-white/90 text-xs font-medium' }, p.label),
          h('div', { class: 'w-9 h-9 rounded-lg bg-white/20 backdrop-blur flex items-center justify-center' }, [
            h('i', { class: `${p.icon} text-base` }),
          ]),
        ]),
        h('p', { class: 'text-3xl font-bold mt-3 tracking-tight' }, p.value ?? '-'),
        h('p', { class: 'text-xs text-white/80 mt-0.5' }, p.unit),
        p.hint ? h('p', { class: 'text-[11px] text-white/70 mt-1 inline-block px-2 py-0.5 rounded bg-white/15 backdrop-blur' }, p.hint) : null,
      ]),
    ])
  },
})

async function fetchData() {
  loading.value = true
  try {
    const params = {}
    if (selectedYear.value) params.year = selectedYear.value
    const { data: res } = await api.get('/public/dashboard', { params })
    data.value = res
  } finally {
    loading.value = false
  }
}

async function fetchYears() {
  try {
    const { data: res } = await api.get('/public/years')
    yearOptions.value = res.map(y => ({ label: `ปี พ.ศ. ${y}`, value: y }))
  } catch {}
}

watch(selectedYear, fetchData)

onMounted(async () => {
  await fetchYears()
  await fetchData()
})
</script>
