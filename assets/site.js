document.querySelectorAll("[data-dates]").forEach((form) => {
  const arrival = form.querySelector("[data-arrival]");
  const departure = form.querySelector("[data-departure]");
  const update = () => {
    let nights = 0;
    departure.min = arrival.min;
    if (arrival.value) {
      const nextDay = new Date(arrival.value + "T00:00:00Z");
      nextDay.setUTCDate(nextDay.getUTCDate() + 1);
      departure.min = nextDay.toISOString().slice(0, 10);
    }
    if (arrival.value && departure.value) {
      nights = Math.round(
        (Date.parse(departure.value) - Date.parse(arrival.value)) / 86400000,
      );
    }
    const output = form.querySelector("[data-total]");
    if (output) {
      const total = Number(output.dataset.total) * nights;
      output.textContent =
        nights >= 1 && nights <= 90
          ? new Intl.NumberFormat("fr-MA", { maximumFractionDigits: 2 }).format(
              total,
            ) +
            " " +
            output.dataset.currency +
            " pour " +
            nights +
            (nights === 1 ? " nuit" : " nuits")
          : "Choisissez vos dates pour voir le total.";
    }
  };
  arrival.addEventListener("change", update);
  departure.addEventListener("change", update);
  update();
});
