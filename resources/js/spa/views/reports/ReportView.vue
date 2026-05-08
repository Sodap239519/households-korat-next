<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-chart-pie text-violet-600"></i>
          รายงาน
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">สรุปผลการดำเนินงานในระบบ — สามารถส่งออกเป็น CSV ได้</p>
      </div>
      <Button label="ส่งออก CSV" icon="fi fi-rr-download" severity="secondary" outlined @click="exportCsv" />
    </div>

    <!-- Tabs -->
    <div class="box-card p-2">
      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="district"><span class="flex items-center gap-2"><i class="fi fi-rr-marker"></i> รายได้รายอำเภอ</span></Tab>
          <Tab value="quota"><span class="flex items-center gap-2"><i class="fi fi-rr-clipboard-list"></i> โควต้า vs จัดสรร</span></Tab>
          <Tab value="enterprise"><span class="flex items-center gap-2"><i class="fi fi-rr-shop"></i> วิสาหกิจ/กลุ่มเพาะเห็ด</span></Tab>
          <Tab value="household"><span class="flex items-center gap-2"><i class="fi fi-rr-house-blank"></i> รายได้ครัวเรือน</span></Tab>
          <Tab value="income"><span class="flex items-center gap-2"><i class="fi fi-rr-chart-mixed-up-circle-dollar"></i> เปรียบเทียบรายได้</span></Tab>
        </TabList>

        <TabPanels>
          <!-- District -->
          <TabPanel value="district">
            <DataTable
              :value="districtData"
              :loading="loadingDistrict"
              stripedRows
              scrollable
              scrollHeight="60vh"
              sortField="total_revenue"
              :sortOrder="-1"
            >
              <template #footer>
                <div class="flex items-center justify-between text-sm px-2 flex-wrap gap-2">
                  <span class="text-slate-600">รวม <span class="font-semibold text-violet-700">{{ districtData.length }}</span> อำเภอ</span>
                  <div class="flex gap-3 text-xs">
                    <span>ครัวเรือน: <span class="font-semibold">{{ sum(districtData, 'participating_households') }}</span></span>
                    <span>ผลผลิต: <span class="font-semibold">{{ fmt(sum(districtData, 'total_harvest_kg'), 2) }}</span> กก.</span>
                    <span>รายได้: <span class="font-semibold text-emerald-700">{{ fmt(sum(districtData, 'total_revenue'), 2) }}</span> บาท</span>
                  </div>
                </div>
              </template>

              <Column header="#" :style="{ width: '60px' }">
                <template #body="{ index }"><span class="text-xs text-slate-400">{{ index + 1 }}</span></template>
              </Column>
              <Column field="district" header="อำเภอ" sortable />
              <Column field="participating_households" header="ครัวเรือน" sortable :style="{ minWidth: '120px' }" />
              <Column field="total_allocated_bags" header="ก้อนจัดสรร" sortable :style="{ minWidth: '130px' }">
                <template #body="{ data }">{{ fmt(data.total_allocated_bags) }}</template>
              </Column>
              <Column field="total_harvest_kg" header="ผลผลิต (กก.)" sortable :style="{ minWidth: '130px' }">
                <template #body="{ data }">{{ fmt(data.total_harvest_kg, 2) }}</template>
              </Column>
              <Column field="total_sold_kg" header="ขาย (กก.)" sortable :style="{ minWidth: '120px' }">
                <template #body="{ data }">{{ fmt(data.total_sold_kg, 2) }}</template>
              </Column>
              <Column field="total_revenue" header="รายได้ (บาท)" sortable :style="{ minWidth: '140px' }">
                <template #body="{ data }">
                  <span class="font-semibold text-emerald-700">{{ fmt(data.total_revenue, 2) }}</span>
                </template>
              </Column>
            </DataTable>
          </TabPanel>

          <!-- Quota -->
          <TabPanel value="quota">
            <DataTable :value="quotaData" :loading="loadingQuota" stripedRows scrollable scrollHeight="60vh">
              <template #footer>
                <div class="flex items-center justify-between text-sm px-2 flex-wrap gap-2">
                  <span class="text-slate-600">รวม <span class="font-semibold text-violet-700">{{ quotaData.length }}</span> รายการ</span>
                  <div class="flex gap-3 text-xs">
                    <span>โควต้า: <span class="font-semibold">{{ fmt(sum(quotaData, 'quota_bags')) }}</span> ก้อน</span>
                    <span>จัดสรร: <span class="font-semibold">{{ fmt(sum(quotaData, 'total_allocated')) }}</span> ก้อน</span>
                    <span>คงเหลือ: <span class="font-semibold text-emerald-700">{{ fmt(sum(quotaData, 'remaining')) }}</span> ก้อน</span>
                  </div>
                </div>
              </template>

              <Column header="#" :style="{ width: '60px' }">
                <template #body="{ index }"><span class="text-xs text-slate-400">{{ index + 1 }}</span></template>
              </Column>
              <Column field="district" header="อำเภอ" sortable />
              <Column header="ปี/รอบ" :style="{ minWidth: '120px' }">
                <template #body="{ data }">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-violet-50 text-violet-700 text-xs border border-violet-200">
                    {{ data.year }} · {{ data.round }}
                  </span>
                </template>
              </Column>
              <Column field="quota_bags" header="โควต้า" sortable>
                <template #body="{ data }">{{ fmt(data.quota_bags) }}</template>
              </Column>
              <Column field="total_allocated" header="จัดสรร" sortable>
                <template #body="{ data }">{{ fmt(data.total_allocated) }}</template>
              </Column>
              <Column field="remaining" header="คงเหลือ" sortable>
                <template #body="{ data }">
                  <span :class="['font-semibold', data.remaining < 0 ? 'text-rose-600' : data.remaining === 0 ? 'text-amber-600' : 'text-emerald-600']">
                    {{ fmt(data.remaining) }}
                  </span>
                </template>
              </Column>
              <Column field="pct_allocated" header="% จัดสรร" sortable :style="{ minWidth: '160px' }">
                <template #body="{ data }">
                  <div class="flex items-center gap-2">
                    <div class="flex-1 h-2 bg-violet-100 rounded-full overflow-hidden">
                      <div class="h-2 bg-gradient-to-r from-violet-500 to-fuchsia-500 rounded-full" :style="{ width: Math.min(data.pct_allocated, 100) + '%' }"></div>
                    </div>
                    <span class="text-xs font-semibold w-10 text-right">{{ data.pct_allocated }}%</span>
                  </div>
                </template>
              </Column>
            </DataTable>
          </TabPanel>

          <!-- Enterprise + Group (2 tables) -->
          <TabPanel value="enterprise">
            <div class="space-y-5">
              <!-- Table 1: วิสาหกิจชุมชน -->
              <div>
                <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                  <i class="fi fi-rr-shop text-fuchsia-600"></i> วิสาหกิจชุมชน
                  <span class="text-xs text-slate-400 font-normal">(จากการบันทึกการขายผ่านวิสาหกิจ)</span>
                </h3>
                <DataTable :value="enterpriseData" :loading="loadingEnterprise" stripedRows scrollable scrollHeight="40vh"
                          sortField="total_revenue" :sortOrder="-1">
                  <template #empty>
                    <div class="text-center py-8 text-slate-400">
                      <i class="fi fi-rr-info text-2xl"></i>
                      <p class="mt-2 text-sm">ยังไม่มีข้อมูลวิสาหกิจ</p>
                    </div>
                  </template>
                  <template #footer>
                    <div class="flex items-center justify-between text-sm px-2 flex-wrap gap-2">
                      <span class="text-slate-600">รวม <span class="font-semibold text-fuchsia-700">{{ enterpriseData.length }}</span> วิสาหกิจ</span>
                      <span class="text-xs">รายได้รวม: <span class="font-semibold text-emerald-700">{{ fmt(sum(enterpriseData, 'total_revenue'), 2) }}</span> บาท</span>
                    </div>
                  </template>
                  <Column header="#" :style="{ width: '60px' }">
                    <template #body="{ index }"><span class="text-xs text-slate-400">{{ index + 1 }}</span></template>
                  </Column>
                  <Column field="enterprise_name" header="วิสาหกิจ" sortable :style="{ minWidth: '200px' }" />
                  <Column field="households_count" header="ครัวเรือน" sortable />
                  <Column field="total_sold_kg" header="ขาย (กก.)" sortable>
                    <template #body="{ data }">{{ fmt(data.total_sold_kg, 2) }}</template>
                  </Column>
                  <Column field="total_revenue" header="รายได้ (บาท)" sortable>
                    <template #body="{ data }">
                      <span class="font-semibold text-emerald-700">{{ fmt(data.total_revenue, 2) }}</span>
                    </template>
                  </Column>
                </DataTable>
              </div>

              <!-- Table 2: กลุ่มเพาะเห็ด -->
              <div>
                <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                  <i class="fi fi-rr-mushroom text-emerald-600"></i> กลุ่มเพาะเห็ด
                  <span class="text-xs text-slate-400 font-normal">(จากการจัดสรรแบบกลุ่ม — group allocation)</span>
                </h3>
                <DataTable :value="groupData" :loading="loadingGroup" stripedRows scrollable scrollHeight="40vh"
                          sortField="total_revenue" :sortOrder="-1">
                  <template #empty>
                    <div class="text-center py-8 text-slate-400">
                      <i class="fi fi-rr-info text-2xl"></i>
                      <p class="mt-2 text-sm">ยังไม่มีกลุ่มเพาะเห็ด — สร้างได้ที่หน้า "การจัดสรร" → เลือก "รายกลุ่ม"</p>
                    </div>
                  </template>
                  <template #footer>
                    <div class="flex items-center justify-between text-sm px-2 flex-wrap gap-2">
                      <span class="text-slate-600">รวม <span class="font-semibold text-emerald-700">{{ groupData.length }}</span> กลุ่ม</span>
                      <div class="flex gap-3 text-xs flex-wrap">
                        <span>ครัวเรือน: <span class="font-semibold">{{ sum(groupData, 'households_count') }}</span></span>
                        <span>ก้อน: <span class="font-semibold">{{ fmt(sum(groupData, 'total_bags')) }}</span></span>
                        <span>ผลผลิต: <span class="font-semibold">{{ fmt(sum(groupData, 'total_harvest_kg'), 2) }}</span> กก.</span>
                        <span>รายได้: <span class="font-semibold text-emerald-700">{{ fmt(sum(groupData, 'total_revenue'), 2) }}</span> บาท</span>
                      </div>
                    </div>
                  </template>
                  <Column header="#" :style="{ width: '60px' }">
                    <template #body="{ index }"><span class="text-xs text-slate-400">{{ index + 1 }}</span></template>
                  </Column>
                  <Column field="group_label" header="ชื่อกลุ่ม" sortable :style="{ minWidth: '200px' }">
                    <template #body="{ data }">
                      <span v-if="data.group_label" class="font-medium text-slate-700">{{ data.group_label }}</span>
                      <span v-else class="text-slate-400 italic">ไม่ระบุชื่อกลุ่ม</span>
                    </template>
                  </Column>
                  <Column field="district" header="อำเภอ" sortable :style="{ minWidth: '120px' }" />
                  <Column field="households_count" header="ครัวเรือน" sortable :style="{ minWidth: '110px' }">
                    <template #body="{ data }">
                      <span class="font-semibold text-violet-700">{{ fmt(data.households_count) }}</span>
                    </template>
                  </Column>
                  <Column field="total_bags" header="ก้อนรวม" sortable :style="{ minWidth: '110px' }">
                    <template #body="{ data }">{{ fmt(data.total_bags) }}</template>
                  </Column>
                  <Column field="total_harvest_kg" header="ผลผลิต (กก.)" sortable :style="{ minWidth: '130px' }">
                    <template #body="{ data }">{{ fmt(data.total_harvest_kg, 2) }}</template>
                  </Column>
                  <Column field="total_sold_kg" header="ขาย (กก.)" sortable :style="{ minWidth: '120px' }">
                    <template #body="{ data }">{{ fmt(data.total_sold_kg, 2) }}</template>
                  </Column>
                  <Column field="total_revenue" header="รายได้ (บาท)" sortable :style="{ minWidth: '140px' }">
                    <template #body="{ data }">
                      <span class="font-semibold text-emerald-700">{{ fmt(data.total_revenue, 2) }}</span>
                    </template>
                  </Column>
                </DataTable>
              </div>
            </div>
          </TabPanel>

          <!-- Household revenue -->
          <TabPanel value="household">
            <div class="mb-3 flex gap-2">
              <IconField class="flex-1">
                <InputIcon class="fi fi-rr-search text-slate-400" />
                <InputText v-model="hhSearch" @input="onHhSearch" placeholder="ค้นหาชื่อ / รหัสบ้าน..." class="w-full" />
              </IconField>
            </div>
            <DataTable :value="householdData" :loading="loadingHousehold" stripedRows scrollable scrollHeight="60vh">
              <template #empty>
                <div class="text-center py-12 text-slate-400">
                  <i class="fi fi-rr-info text-3xl"></i>
                  <p class="mt-2">ไม่พบข้อมูล</p>
                </div>
              </template>
              <template #footer>
                <div class="text-sm text-slate-600 px-2">
                  รวม <span class="font-semibold text-violet-700">{{ householdMeta.total || 0 }}</span> ครัวเรือน · รายได้รวมในหน้านี้
                  <span class="font-semibold text-emerald-700 ml-2">{{ fmt(sum(householdData, 'total_revenue'), 2) }}</span> บาท
                </div>
              </template>

              <Column header="#" :style="{ width: '60px' }">
                <template #body="{ index }">
                  <span class="text-xs text-slate-400">{{ ((householdMeta.current_page || 1) - 1) * 20 + index + 1 }}</span>
                </template>
              </Column>
              <Column field="household_code" header="รหัสบ้าน" sortable :style="{ minWidth: '130px' }">
                <template #body="{ data }">
                  <span class="font-mono text-violet-700 font-medium">{{ data.household_code }}</span>
                </template>
              </Column>
              <Column field="full_name" header="ชื่อ-นามสกุล" sortable :style="{ minWidth: '180px' }" />
              <Column field="district" header="อำเภอ" sortable />
              <Column field="allocation_count" header="จัดสรร" sortable>
                <template #body="{ data }">{{ fmt(data.allocation_count) }}</template>
              </Column>
              <Column field="total_sold_kg" header="ขาย (กก.)" sortable>
                <template #body="{ data }">{{ fmt(data.total_sold_kg, 2) }}</template>
              </Column>
              <Column field="total_revenue" header="รายได้ (บาท)" sortable>
                <template #body="{ data }">
                  <span class="font-semibold text-emerald-700">{{ fmt(data.total_revenue, 2) }}</span>
                </template>
              </Column>
            </DataTable>
            <Pagination :meta="householdMeta" @change="onHhPage" class="mt-3 px-2" />
          </TabPanel>

          <!-- Income comparison: district summary + household table with search -->
          <TabPanel value="income">
            <div v-if="loadingIncome" class="text-center py-12 text-violet-400">
              <i class="fi fi-rr-loading text-3xl animate-spin"></i>
            </div>
            <div v-else class="space-y-5">
              <!-- Summary banner -->
              <div class="rounded-xl bg-gradient-to-r from-emerald-50 via-teal-50 to-cyan-50 border-2 border-emerald-200 p-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow">
                      <i class="fi fi-rr-bullseye"></i>
                    </div>
                    <div>
                      <p class="text-xs text-emerald-700 font-semibold">เป้าหมายระบบ</p>
                      <p class="text-sm text-slate-700">ทุกครัวเรือนต้องมีรายได้เพิ่มขึ้น <span class="font-bold text-emerald-700">≥ {{ income.summary?.target_pct ?? 15 }}%</span></p>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                    <div class="text-center px-3 py-2 bg-white rounded-lg border border-emerald-100">
                      <p class="text-slate-500">ครัวเรือน</p>
                      <p class="font-bold text-slate-800">{{ fmt(income.summary?.total_households) }}</p>
                    </div>
                    <div class="text-center px-3 py-2 bg-white rounded-lg border border-emerald-100">
                      <p class="text-slate-500">มีรายได้จากขาย</p>
                      <p class="font-bold text-cyan-700">{{ fmt(income.summary?.with_sales) }}</p>
                    </div>
                    <div class="text-center px-3 py-2 bg-white rounded-lg border border-emerald-100">
                      <p class="text-slate-500">ผ่านเป้า</p>
                      <p class="font-bold text-emerald-700">{{ fmt(income.summary?.passed_target) }}</p>
                    </div>
                    <div class="text-center px-3 py-2 bg-white rounded-lg border border-emerald-100">
                      <p class="text-slate-500">ยังไม่ผ่าน</p>
                      <p class="font-bold text-rose-700">{{ fmt(income.summary?.failed_target) }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- District table -->
              <div class="box-card p-0 overflow-hidden">
                <div class="px-4 py-3 border-b border-violet-100 flex items-center gap-2 bg-violet-50/30">
                  <i class="fi fi-rr-marker text-violet-600"></i>
                  <h3 class="text-sm font-semibold text-slate-700">สรุปรายอำเภอ</h3>
                </div>
                <DataTable :value="income.byDistrict || []" stripedRows scrollable scrollHeight="50vh">
                  <Column field="district" header="อำเภอ" sortable />
                  <Column header="ครัวเรือน" sortable :style="{ minWidth: '120px' }">
                    <template #body="{ data }">{{ fmt(data.with_sales_count) }}/{{ fmt(data.households_count) }}</template>
                  </Column>
                  <Column header="ผ่านเป้า" :style="{ minWidth: '100px' }">
                    <template #body="{ data }">
                      <span class="text-emerald-700 font-semibold">{{ fmt(data.passed_count) }}</span>
                      <span class="text-slate-400 text-xs"> /{{ fmt(data.with_sales_count) }}</span>
                    </template>
                  </Column>
                  <Column field="total_survey_income" header="รายได้สำรวจ (บาท)" sortable :style="{ minWidth: '160px' }">
                    <template #body="{ data }">{{ fmt(data.total_survey_income, 0) }}</template>
                  </Column>
                  <Column field="total_sales_revenue" header="รายได้ขาย (บาท)" sortable :style="{ minWidth: '150px' }">
                    <template #body="{ data }">
                      <span class="font-semibold text-emerald-700">{{ fmt(data.total_sales_revenue, 0) }}</span>
                    </template>
                  </Column>
                  <Column field="increase_amount" header="เพิ่ม/ลด (บาท)" sortable :style="{ minWidth: '140px' }">
                    <template #body="{ data }">
                      <span :class="['font-semibold', Number(data.increase_amount) >= 0 ? 'text-emerald-700' : 'text-rose-700']">
                        {{ Number(data.increase_amount) >= 0 ? '+' : '' }}{{ fmt(data.increase_amount, 0) }}
                      </span>
                    </template>
                  </Column>
                  <Column field="increase_pct" header="% เพิ่ม" sortable :style="{ minWidth: '110px' }">
                    <template #body="{ data }">
                      <span :class="['font-bold', pctClass(data.increase_pct)]">
                        <span v-if="data.is_new_income" v-tooltip.top="'รายได้ใหม่ทั้งหมด (ไม่มีรายได้สำรวจ)'">+∞%</span>
                        <template v-else>
                          {{ data.increase_pct == null ? '-' : (data.increase_pct >= 0 ? '+' : '') + Number(data.increase_pct).toFixed(2) + '%' }}
                        </template>
                      </span>
                    </template>
                  </Column>
                  <Column header="สถานะ" :style="{ minWidth: '120px' }">
                    <template #body="{ data }">
                      <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border',
                        data.passed_target ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200']">
                        <i :class="data.passed_target ? 'fi fi-rr-check' : 'fi fi-rr-cross-small'"></i>
                        {{ data.passed_target ? 'ผ่าน' : 'ยังไม่ผ่าน' }}
                      </span>
                    </template>
                  </Column>
                </DataTable>
              </div>

              <!-- Household table with search -->
              <div class="box-card p-0 overflow-hidden">
                <div class="px-4 py-3 border-b border-violet-100 bg-violet-50/30">
                  <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
                    <div class="flex items-center gap-2">
                      <i class="fi fi-rr-house-blank text-violet-600"></i>
                      <h3 class="text-sm font-semibold text-slate-700">
                        รายครัวเรือน — เฉพาะที่มีการขาย
                        <span class="text-slate-500 font-normal text-xs">({{ fmt(income.matched_count) }} ราย)</span>
                      </h3>
                    </div>
                  </div>
                  <IconField>
                    <InputIcon class="fi fi-rr-search text-slate-400" />
                    <InputText
                      v-model="incomeSearch"
                      @input="onIncomeSearch"
                      placeholder="ค้นหาชื่อ / บ้านเลขที่ / อำเภอ / รหัสบ้าน..."
                      class="w-full"
                    />
                  </IconField>
                </div>
                <DataTable
                  :value="income.byHousehold || []"
                  stripedRows
                  scrollable
                  scrollHeight="60vh"
                  paginator
                  :rows="20"
                  :rowsPerPageOptions="[10, 20, 50, 100]"
                  paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                  currentPageReportTemplate="แสดง {first}–{last} จาก {totalRecords} ราย"
                >
                  <template #empty>
                    <div class="text-center py-12 text-slate-400">
                      <i class="fi fi-rr-info text-3xl"></i>
                      <p class="mt-2">{{ incomeSearch ? 'ไม่พบครัวเรือนที่ตรงกับคำค้น' : 'ยังไม่มีครัวเรือนที่บันทึกการขาย' }}</p>
                    </div>
                  </template>
                  <template #footer>
                    <div class="flex items-center justify-between text-sm px-2 flex-wrap gap-3 bg-violet-50/40 -mx-3 -mb-3 px-4 py-3 border-t-2 border-violet-200">
                      <div class="flex items-center gap-3 flex-wrap text-xs">
                        <span class="font-semibold text-slate-700">
                          <i class="fi fi-rr-calculator text-violet-600"></i> รวม {{ income.byHousehold?.length || 0 }} ราย
                        </span>
                        <span class="text-emerald-700">· ผ่านเป้า {{ incomePassedCount }}</span>
                        <span class="text-rose-700">· ยังไม่ผ่าน {{ incomeFailedCount }}</span>
                      </div>
                      <div class="flex items-center gap-3 text-xs flex-wrap">
                        <span>สำรวจรวม: <span class="font-semibold text-slate-700">{{ fmt(incomeTotalSurvey, 0) }}</span></span>
                        <span>ขายรวม: <span class="font-semibold text-emerald-700">{{ fmt(incomeTotalSales, 0) }}</span></span>
                        <span>เพิ่ม/ลด:
                          <span :class="['font-semibold', incomeTotalDiff >= 0 ? 'text-emerald-700' : 'text-rose-700']">
                            {{ incomeTotalDiff >= 0 ? '+' : '' }}{{ fmt(incomeTotalDiff, 0) }}
                          </span>
                        </span>
                      </div>
                    </div>
                  </template>
                  <Column header="#" :style="{ width: '60px' }">
                    <template #body="{ index }"><span class="text-xs text-slate-400">{{ index + 1 }}</span></template>
                  </Column>
                  <Column header="ครัวเรือน" :style="{ minWidth: '200px' }" sortable sortField="name">
                    <template #body="{ data }">
                      <div class="font-medium text-slate-700 flex items-center gap-2">
                        {{ data.name }}
                        <span v-if="data.is_new_income"
                              v-tooltip.top="'รายได้ใหม่ — ไม่มีรายได้สำรวจ'"
                              class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-cyan-100 text-cyan-700 text-[10px] font-medium border border-cyan-200">
                          <i class="fi fi-rr-sparkles"></i> ใหม่
                        </span>
                      </div>
                      <div class="font-mono text-[11px] text-violet-600">{{ data.household_code }}</div>
                    </template>
                  </Column>
                  <Column field="house_number" header="บ้านเลขที่" sortable :style="{ minWidth: '110px' }">
                    <template #body="{ data }">
                      <span class="text-slate-600">{{ data.house_number || '-' }}</span>
                    </template>
                  </Column>
                  <Column field="district" header="อำเภอ" sortable :style="{ minWidth: '120px' }" />
                  <Column field="survey_income" header="รายได้สำรวจ/เดือน" sortable :style="{ minWidth: '150px' }">
                    <template #body="{ data }">{{ fmt(data.survey_income, 0) }}</template>
                  </Column>
                  <Column field="sales_revenue" header="รายได้ขายรวม" sortable :style="{ minWidth: '140px' }">
                    <template #body="{ data }">
                      <span class="font-semibold text-emerald-700">{{ fmt(data.sales_revenue, 0) }}</span>
                    </template>
                  </Column>
                  <Column field="increase_amount" header="เพิ่ม/ลด" sortable :style="{ minWidth: '120px' }">
                    <template #body="{ data }">
                      <span :class="['font-semibold', Number(data.increase_amount) >= 0 ? 'text-emerald-700' : 'text-rose-700']">
                        {{ Number(data.increase_amount) >= 0 ? '+' : '' }}{{ fmt(data.increase_amount, 0) }}
                      </span>
                    </template>
                  </Column>
                  <Column field="increase_pct" header="% เพิ่ม" sortable :style="{ minWidth: '110px' }">
                    <template #body="{ data }">
                      <span :class="['font-bold', pctClass(data.increase_pct)]">
                        <span v-if="data.is_new_income" v-tooltip.top="'รายได้ใหม่ทั้งหมด (ไม่มีรายได้สำรวจ)'">+∞%</span>
                        <template v-else>
                          {{ data.increase_pct == null ? '-' : (data.increase_pct >= 0 ? '+' : '') + Number(data.increase_pct).toFixed(2) + '%' }}
                        </template>
                      </span>
                    </template>
                  </Column>
                  <Column header="สถานะ" :style="{ minWidth: '120px' }">
                    <template #body="{ data }">
                      <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border',
                        data.passed_target ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200']">
                        <i :class="data.passed_target ? 'fi fi-rr-check' : 'fi fi-rr-cross-small'"></i>
                        {{ data.passed_target ? 'ผ่าน' : 'ยังไม่ผ่าน' }}
                      </span>
                    </template>
                  </Column>
                </DataTable>
              </div>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>

    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Toast from 'primevue/toast'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip
import Pagination from '../components/Pagination.vue'

const toast = useToast()
const activeTab = ref('district')

const districtData    = ref([])
const quotaData       = ref([])
const enterpriseData  = ref([])
const groupData       = ref([])
const householdData   = ref([])
const householdMeta   = ref({})
const loadingDistrict   = ref(false)
const loadingQuota      = ref(false)
const loadingEnterprise = ref(false)
const loadingGroup      = ref(false)
const loadingHousehold  = ref(false)
const hhSearch = ref('')
let hhPage = 1, hhTimer = null

// Income comparison
const income = ref({})
const loadingIncome = ref(false)
const incomeSearch = ref('')
let incomeTimer = null

function pctClass(p) {
  if (p == null) return 'text-slate-400'
  if (p >= 15)   return 'text-emerald-700'
  if (p >= 0)    return 'text-amber-700'
  return 'text-rose-700'
}

// Income comparison: footer totals (computed from full filtered list)
const incomeTotalSurvey = computed(() =>
  (income.value.byHousehold || []).reduce((a, h) => a + Number(h.survey_income || 0), 0)
)
const incomeTotalSales = computed(() =>
  (income.value.byHousehold || []).reduce((a, h) => a + Number(h.sales_revenue || 0), 0)
)
const incomeTotalDiff = computed(() => incomeTotalSales.value - incomeTotalSurvey.value)
const incomePassedCount = computed(() =>
  (income.value.byHousehold || []).filter(h => h.passed_target).length
)
const incomeFailedCount = computed(() =>
  (income.value.byHousehold || []).filter(h => !h.passed_target).length
)

function fmt(v, dec = 0) {
  if (v == null) return '-'
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}
function sum(arr, key) {
  return (arr || []).reduce((acc, x) => acc + Number(x[key] || 0), 0)
}

async function loadDistrict() {
  loadingDistrict.value = true
  try {
    const { data } = await api.get('/reports/by-district')
    districtData.value = data
  } finally { loadingDistrict.value = false }
}
async function loadQuota() {
  loadingQuota.value = true
  try {
    const { data } = await api.get('/reports/quota-vs-allocated')
    quotaData.value = data
  } finally { loadingQuota.value = false }
}
async function loadEnterprise() {
  loadingEnterprise.value = true
  try {
    const { data } = await api.get('/reports/by-enterprise')
    enterpriseData.value = data
  } finally { loadingEnterprise.value = false }
}
async function loadGroup() {
  loadingGroup.value = true
  try {
    const { data } = await api.get('/reports/by-group')
    groupData.value = data
  } finally { loadingGroup.value = false }
}
async function loadHousehold() {
  loadingHousehold.value = true
  try {
    const params = { page: hhPage, per_page: 20 }
    if (hhSearch.value) params.search = hhSearch.value
    const { data } = await api.get('/reports/household-revenue', { params })
    householdData.value = data.data
    householdMeta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } finally { loadingHousehold.value = false }
}

function onHhSearch() {
  clearTimeout(hhTimer)
  hhTimer = setTimeout(() => { hhPage = 1; loadHousehold() }, 300)
}
function onHhPage(p) { hhPage = p; loadHousehold() }

async function loadIncome() {
  loadingIncome.value = true
  try {
    const params = {}
    if (incomeSearch.value) params.search = incomeSearch.value
    const { data } = await api.get('/reports/income-comparison', { params })
    income.value = data
  } finally { loadingIncome.value = false }
}
function onIncomeSearch() {
  clearTimeout(incomeTimer)
  incomeTimer = setTimeout(() => loadIncome(), 300)
}

watch(activeTab, (tab) => {
  if (tab === 'district'   && !districtData.value.length)   loadDistrict()
  if (tab === 'quota'      && !quotaData.value.length)      loadQuota()
  if (tab === 'enterprise') {
    if (!enterpriseData.value.length) loadEnterprise()
    if (!groupData.value.length)      loadGroup()
  }
  if (tab === 'household'  && !householdData.value.length)  loadHousehold()
  if (tab === 'income'     && !income.value.summary)        loadIncome()
})

// CSV export — derives from currently active tab's data
function exportCsv() {
  const tab = activeTab.value
  let filename = ''
  let headers = []
  let rows = []

  if (tab === 'district') {
    filename = 'report_by_district'
    headers = ['อำเภอ', 'ครัวเรือน', 'ก้อนจัดสรร', 'ผลผลิต(กก.)', 'ขาย(กก.)', 'รายได้(บาท)']
    rows = districtData.value.map(r => [r.district, r.participating_households, r.total_allocated_bags, r.total_harvest_kg, r.total_sold_kg, r.total_revenue])
  } else if (tab === 'quota') {
    filename = 'report_quota_vs_allocated'
    headers = ['อำเภอ', 'ปี', 'รอบ', 'โควต้า', 'จัดสรร', 'คงเหลือ', '%จัดสรร']
    rows = quotaData.value.map(r => [r.district, r.year, r.round, r.quota_bags, r.total_allocated, r.remaining, r.pct_allocated])
  } else if (tab === 'enterprise') {
    // Combined export: 2 sections (enterprises then groups)
    filename = 'report_enterprise_and_groups'
    headers = ['ประเภท', 'ชื่อ', 'อำเภอ', 'ครัวเรือน', 'ก้อนรวม', 'ผลผลิต(กก.)', 'ขาย(กก.)', 'รายได้(บาท)']
    rows = [
      ...enterpriseData.value.map(r => ['วิสาหกิจ', r.enterprise_name, '', r.households_count, '', '', r.total_sold_kg, r.total_revenue]),
      ...groupData.value.map(r => ['กลุ่มเพาะเห็ด', r.group_label || '(ไม่ระบุชื่อ)', r.district || '', r.households_count, r.total_bags, r.total_harvest_kg, r.total_sold_kg, r.total_revenue]),
    ]
  } else if (tab === 'household') {
    filename = 'report_household_revenue'
    headers = ['รหัสบ้าน', 'ชื่อ-นามสกุล', 'อำเภอ', 'จัดสรร', 'ขาย(กก.)', 'รายได้(บาท)']
    rows = householdData.value.map(r => [r.household_code, r.full_name, r.district, r.allocation_count, r.total_sold_kg, r.total_revenue])
  } else if (tab === 'income') {
    filename = 'report_income_comparison'
    headers = ['รหัสบ้าน', 'ชื่อ-นามสกุล', 'บ้านเลขที่', 'อำเภอ', 'รายได้สำรวจ/เดือน', 'รายได้ขายรวม', 'เพิ่ม/ลด', '%เพิ่ม', 'สถานะ']
    rows = (income.value.byHousehold || []).map(r => [
      r.household_code, r.name, r.house_number ?? '', r.district ?? '',
      r.survey_income, r.sales_revenue, r.increase_amount,
      r.is_new_income ? 'รายได้ใหม่' : (r.increase_pct == null ? '' : r.increase_pct),
      r.passed_target ? (r.is_new_income ? 'ผ่าน (รายได้ใหม่)' : 'ผ่าน') : 'ยังไม่ผ่าน',
    ])
  }

  if (!rows.length) {
    toast.add({ severity: 'warn', summary: 'ไม่มีข้อมูล', detail: 'แท็บนี้ยังไม่มีข้อมูลให้ส่งออก', life: 2500 })
    return
  }

  const csv = '﻿' + [headers, ...rows].map(line =>
    line.map(v => {
      const s = String(v ?? '')
      return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s
    }).join(',')
  ).join('\n')

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${filename}_${new Date().toISOString().slice(0, 10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
  toast.add({ severity: 'success', summary: 'ส่งออกสำเร็จ', life: 2000 })
}

onMounted(loadDistrict)
</script>
