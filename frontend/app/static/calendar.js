// Week / month calendar (FullCalendar 6). Clicking an event opens its edit form,
// selecting a range opens the "add" choice (all-day) or a prefilled work form (timed).

(function () {
  const el = document.getElementById("calendar");
  const view = el.dataset.view;

  function isoDate(d) {
    return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
  }

  function isoLocal(d) {
    return isoDate(d) + "T" + d.toTimeString().slice(0, 8);
  }

  function openForm(url) {
    htmx.ajax("GET", url, { target: "#modal-content" });
  }

  function loadTotals(date) {
    htmx.ajax("GET", `/calendar-data/totals?view=${view}&date=${isoDate(date)}`, { target: "#cal-totals" });
  }

  const calendar = new FullCalendar.Calendar(el, {
    initialView: view === "month" ? "dayGridMonth" : "timeGridWeek",
    initialDate: el.dataset.date || undefined,
    firstDay: 1,
    weekNumbers: true,
    locale: "en-GB",
    height: "auto",
    slotMinTime: "05:00:00",
    nowIndicator: true,
    selectable: true,
    headerToolbar: { left: "prev,next today", center: "title", right: "" },
    eventTimeFormat: { hour: "2-digit", minute: "2-digit", hour12: false },
    slotLabelFormat: { hour: "2-digit", minute: "2-digit", hour12: false },
    events: "/calendar-data/events",
    eventClick(info) {
      info.jsEvent.preventDefault();
      const { kind, itemId } = info.event.extendedProps;
      if (kind === "work") openForm(`/forms/work?id=${itemId}`);
      else if (kind === "break") openForm(`/forms/break?id=${itemId}`);
      else if (kind) openForm(`/forms/absence/${kind}?id=${itemId}`);
    },
    select(info) {
      if (info.allDay) {
        const end = new Date(info.end.getTime() - 86400000);
        openForm(`/forms/choice?start=${isoDate(info.start)}&end=${isoDate(end)}`);
      } else {
        openForm(`/forms/work?start=${isoLocal(info.start)}&end=${isoLocal(info.end)}`);
      }
      calendar.unselect();
    },
    datesSet(info) {
      const middle = new Date((info.start.getTime() + info.end.getTime()) / 2);
      const date = view === "month" ? middle : info.start;
      loadTotals(date);
      document.getElementById("list-link").href = `/summary/${view}?date=${isoDate(date)}`;
    },
  });
  calendar.render();

  document.body.addEventListener("dataChanged", () => {
    calendar.refetchEvents();
    const start = calendar.view.currentStart;
    loadTotals(view === "month" ? new Date(start.getTime() + 15 * 86400000) : start);
  });
})();
