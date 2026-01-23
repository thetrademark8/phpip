<script setup>
import { ref, watch } from "vue";
import { reactiveOmit } from "@vueuse/core";
import { RangeCalendarRoot, useForwardPropsEmits } from "reka-ui";
import { today, getLocalTimeZone } from "@internationalized/date";
import { cn } from "@/lib/utils";
import {
  RangeCalendarCell,
  RangeCalendarCellTrigger,
  RangeCalendarGrid,
  RangeCalendarGridBody,
  RangeCalendarGridHead,
  RangeCalendarGridRow,
  RangeCalendarHeadCell,
  RangeCalendarHeader,
  RangeCalendarHeading,
  RangeCalendarNextButton,
  RangeCalendarPrevButton,
} from ".";
import CalendarMonthYearSelect from "@/components/ui/calendar/CalendarMonthYearSelect.vue";

const props = defineProps({
  defaultValue: { type: null, required: false },
  defaultPlaceholder: { type: null, required: false },
  placeholder: { type: null, required: false },
  pagedNavigation: { type: Boolean, required: false },
  preventDeselect: { type: Boolean, required: false },
  weekStartsOn: { type: Number, required: false },
  weekdayFormat: { type: String, required: false },
  calendarLabel: { type: String, required: false },
  fixedWeeks: { type: Boolean, required: false },
  maxValue: { type: null, required: false },
  minValue: { type: null, required: false },
  locale: { type: String, required: false },
  numberOfMonths: { type: Number, required: false },
  disabled: { type: Boolean, required: false },
  readonly: { type: Boolean, required: false },
  initialFocus: { type: Boolean, required: false },
  isDateDisabled: { type: Function, required: false },
  isDateUnavailable: { type: Function, required: false },
  dir: { type: String, required: false },
  nextPage: { type: Function, required: false },
  prevPage: { type: Function, required: false },
  modelValue: { type: null, required: false },
  disableDaysOutsideCurrentView: { type: Boolean, required: false },
  asChild: { type: Boolean, required: false },
  as: { type: [String, Object, Function], required: false },
  class: { type: null, required: false },
  showMonthYearSelect: { type: Boolean, required: false, default: false },
  minYear: { type: Number, required: false },
  maxYear: { type: Number, required: false },
});
const emits = defineEmits(["update:modelValue", "update:placeholder"]);

const delegatedProps = reactiveOmit(props, "class", "showMonthYearSelect", "minYear", "maxYear", "placeholder");

const forwarded = useForwardPropsEmits(delegatedProps, emits);

// Local placeholder that we control
const localPlaceholder = ref(props.placeholder ?? props.defaultPlaceholder ?? today(getLocalTimeZone()));

// Sync with external placeholder prop if provided
watch(() => props.placeholder, (val) => {
  if (val) localPlaceholder.value = val;
});

// When local placeholder changes, emit to parent
watch(localPlaceholder, (val) => {
  emits("update:placeholder", val);
});

// Simple function to update placeholder - called by CalendarMonthYearSelect
function setPlaceholder(newDate) {
  localPlaceholder.value = newDate;
}
</script>

<template>
  <RangeCalendarRoot
    v-slot="{ grid, weekDays }"
    v-model:placeholder="localPlaceholder"
    data-slot="range-calendar"
    :class="cn('p-3', props.class)"
    v-bind="forwarded"
  >
    <RangeCalendarHeader>
      <CalendarMonthYearSelect
        v-if="showMonthYearSelect"
        :placeholder="localPlaceholder"
        :locale="locale"
        :min-year="minYear"
        :max-year="maxYear"
        @update:placeholder="setPlaceholder"
      />
      <RangeCalendarHeading v-else />

      <div class="flex items-center gap-1">
        <RangeCalendarPrevButton />
        <RangeCalendarNextButton />
      </div>
    </RangeCalendarHeader>

    <div class="flex flex-col gap-y-4 mt-4 sm:flex-row sm:gap-x-4 sm:gap-y-0">
      <RangeCalendarGrid v-for="month in grid" :key="month.value.toString()">
        <RangeCalendarGridHead>
          <RangeCalendarGridRow>
            <RangeCalendarHeadCell v-for="day in weekDays" :key="day">
              {{ day }}
            </RangeCalendarHeadCell>
          </RangeCalendarGridRow>
        </RangeCalendarGridHead>
        <RangeCalendarGridBody>
          <RangeCalendarGridRow
            v-for="(weekDates, index) in month.rows"
            :key="`weekDate-${index}`"
            class="mt-2 w-full"
          >
            <RangeCalendarCell
              v-for="weekDate in weekDates"
              :key="weekDate.toString()"
              :date="weekDate"
            >
              <RangeCalendarCellTrigger :day="weekDate" :month="month.value" />
            </RangeCalendarCell>
          </RangeCalendarGridRow>
        </RangeCalendarGridBody>
      </RangeCalendarGrid>
    </div>
  </RangeCalendarRoot>
</template>
