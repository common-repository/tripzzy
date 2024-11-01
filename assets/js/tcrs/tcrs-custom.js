// id
// unit_position
// unit
// before_field
// placeholder

const RangeSliderCustom = (params) => {
  document.querySelectorAll(".tripzzy-tc-range-slider").forEach((slider) => {
    const round = slider.getAttribute("round");
    const unit = slider.getAttribute("generate-labels-units");
    const unitPosition = slider.getAttribute("unit_position");

    const css_id = slider.getAttribute("id");

    slider.generateLabelsFormat = (value) => {
      if (value == undefined) return "";

      let valueFormat = Number(value);
      if (round) {
        valueFormat = valueFormat.toFixed(round);
      }
      switch (unitPosition) {
        case "left":
          return `${unit}` + valueFormat;
          break;
        case "left_with_space":
          return `${unit} ` + valueFormat;
          break;
        case "right_with_space":
          return valueFormat + ` ${unit}`;
          break;
        default:
          return valueFormat + `${unit}`;
          break;
      }
    };
    slider.addEventListener("change", (evt) => {
      const values = evt.detail.values;
      values.map((v, i) => {
        const id = css_id + "-val" + (i + 1);
        const inp = document.getElementById(id);
        inp.setAttribute("value", v);
      });
    });

    if (!!slider.shadowRoot) {
      var shadowEl = slider.shadowRoot;
      // And for example:
      const rangeSliderBox = shadowEl.querySelector(".range-slider-box");
      const labelsRow = shadowEl.querySelector(".labels-row");
      const row = shadowEl.querySelector(".row");
      const rootElem = slider.shadowRoot.host;
      const wrapperElem = rootElem.closest(
        ".tripzzy-range-slider-input-wrapper"
      );
      const beforeField = slider.getAttribute("before_field");
      const placeholder = slider.getAttribute("placeholder");

      labelsRow.classList.add("loaded");
      wrapperElem.classList.add("loaded");

      rangeSliderBox.classList.add("tripzzy-range-slider-box");
      labelsRow.classList.add("tripzzy-labels-row");
      if (beforeField) {
        labelsRow.classList.add("has-before-field");
      }

      row.classList.add("tripzzy-row");

      let isShow = false;
      labelsRow.addEventListener("click", () => {
        isShow = !isShow;
        labelsRow.classList.toggle("show", isShow);
        row.classList.toggle("show", isShow);
        wrapperElem.classList.toggle("show", isShow);
      });

      let attrValue = slider.getAttribute("value");
      let attrValue1 = slider.getAttribute("value1");
      let attrValue2 = slider.getAttribute("value2");
      let attrMin = parseFloat(slider.getAttribute("min") ?? 0);
      let attrMax = parseFloat(slider.getAttribute("max") ?? 100);

      // label override initially.
      let label1 = shadowEl.querySelector(".labels-row .value1-label");
      let label2 = shadowEl.querySelector(".labels-row .value2-label");

      if (null !== attrValue2) {
        // typeof attrValue2 is object
        // multi range
        attrValue1 = parseFloat(attrValue1);
        if (!(attrValue1 > attrMin)) {
          label1.innerHTML = placeholder;
          label2.innerHTML = "";
          label2.classList.add("has-placeholder");
        }
      } else {
        attrValue = parseFloat(attrValue);
        if (!(attrValue > attrMin)) {
          label1.innerHTML = placeholder;
        }
      }
    }

    const checkboxes = document.querySelectorAll(".tripzzy-range-slider-input");
    // Function to manually trigger the change event
    function triggerCheckboxChangeEvent(checkbox, checked) {
      // Update the checkbox state
      checkbox.checked = checked;

      // Create a new change event
      const event = new Event("change", {
        bubbles: true,
        cancelable: true,
      });

      // Dispatch the change event
      checkbox.dispatchEvent(event);
      // To Remove dash between selected range.
      let label2 = shadowEl.querySelector(".labels-row .value2-label");

      if (label2) {
        label2.classList.remove("has-placeholder");
      }
    }

    slider.addEventListener("onMouseUp", () => {
      triggerCheckboxChangeEvent(checkboxes[0], true);
    });
  });
};

window.addEventListener("load", () => {
  RangeSliderCustom(window.RangeSliderCustomOptions);
});
