 /********** MAPPING DEGLI INDICATORI **********/
    const indicatorMapping = {
      "NY.GDP.MKTP.CD": "GDP (current US$)",
      "NY.GDP.PCAP.CD": "GDP per capita (current US$)",
      "SP.POP.TOTL": "Population",
      "SP.DYN.LE00.IN": "Life Expectancy at Birth",
      "SL.UEM.TOTL.ZS": "Unemployment Rate (%)",
      "SE.PRM.TENR": "Primary Enrollment Rate (%)",
      "SE.SEC.ENRR": "Secondary Enrollment Rate (%)",
      "SE.TER.ENRR": "Tertiary Enrollment Rate (%)",
      "SH.XPD.CHEX.GD.ZS": "Health Expenditure (% of GDP)",
      "EN.ATM.CO2E.KT": "CO2 Emissions (kt)",
      "SP.URB.TOTL.IN.ZS": "Urban Population (% of Total)",
      "SP.RUR.TOTL.ZS": "Rural Population (% of Total)",
      "BX.KLT.DINV.CD.WD": "Net FDI (current US$)",
      "FP.CPI.TOTL.ZG": "Inflation, consumer prices (annual %)",
      "IT.CEL.SETS.P2": "Mobile Cellular Subscriptions (per 100 ppl)",
      "EG.ELC.ACCS.ZS": "Access to Electricity (% of population)",
      "SE.XPD.TOTL.GD.ZS": "R&D Expenditure (% of GDP)",
      "SP.DYN.IMRT.IN": "Infant Mortality Rate (per 1,000 live births)"
    };

    // Popola il multi-select per gli indicatori
    const indicatorSelect = document.getElementById("indicatorSelect");
    for (const code in indicatorMapping) {
      const option = document.createElement("option");
      option.value = code;
      option.textContent = indicatorMapping[code];
      indicatorSelect.appendChild(option);
    }
    
    /********** GESTIONE DELLA SCALA DEI DATI (DATA SCOPE) **********/
    const dataScope = document.getElementById("dataScope");
    const countryGroup = document.getElementById("countryGroup");
    const continentGroup = document.getElementById("continentGroup");
    
    dataScope.addEventListener("change", function() {
      const scope = dataScope.value;
      if (scope === "country") {
        countryGroup.style.display = "block";
        continentGroup.style.display = "none";
      } else if (scope === "continent") {
        countryGroup.style.display = "none";
        continentGroup.style.display = "block";
      } else { // "world"
        countryGroup.style.display = "none";
        continentGroup.style.display = "none";
      }
    });
    
    /********** CHART.JS SETUP **********/
    let chartInstance = null;
    function updateChart(datasets) {
      const ctx = document.getElementById("chartCanvas").getContext("2d");
      if (chartInstance) { chartInstance.destroy(); }
      chartInstance = new Chart(ctx, {
        type: 'line',
        data: { datasets: datasets },
        options: {
          responsive: true,
          plugins: {
            legend: { display: true },
            tooltip: { mode: 'index', intersect: false }
          },
          parsing: false,
          scales: {
            x: {
              type: 'linear',
              position: 'bottom',
              title: { display: true, text: 'Year' },
              ticks: { stepSize: 1 }
            },
            y: { title: { display: true, text: 'Value' } }
          }
        }
      });
    }
    
    /********** CARICAMENTO DEI DATI DAL WORLD BANK API **********/
    document.getElementById("loadDataButton").addEventListener("click", function() {
      // Determina il codice da usare in base allo scope: country, continent o world
      let code;
      const scope = dataScope.value;
      if (scope === "world") {
        code = "WLD";
      } else if (scope === "continent") {
        const continentSelect = document.getElementById("continentSelect");
        code = continentSelect.value;
      } else { // country
        const countrySelect = document.getElementById("countrySelect");
        code = countrySelect.value;
      }
      
      const selectedOptions = Array.from(indicatorSelect.selectedOptions);
      if (selectedOptions.length === 0) {
        alert("Seleziona almeno un indicatore.");
        return;
      }
      const indicators = selectedOptions.map(opt => opt.value);
      const startYear = document.getElementById("startYear").value;
      const endYear = document.getElementById("endYear").value;
      
      // Per ciascun indicatore, compone l'URL per l'API e fa fetch dei dati
      const promises = indicators.map(indicatorCode => {
        const url = `https://api.worldbank.org/v2/country/${code}/indicator/${indicatorCode}?format=json&date=${startYear}:${endYear}&per_page=100`;
        return fetch(url)
          .then(response => response.json())
          .then(data => {
            if (!data[1]) return { indicator: indicatorCode, records: [] };
            const records = data[1]
              .filter(item => item.value !== null)
              .map(item => ({
                year: parseInt(item.date),
                value: item.value
              }))
              .sort((a, b) => a.year - b.year);
            return { indicator: indicatorCode, records: records };
          });
      });
      
      Promise.all(promises)
        .then(results => {
          // Crea per ciascun indicatore un dataset per Chart.js
          const datasets = results.map((result, idx) => {
            const dataPoints = result.records.map(record => ({
              x: record.year,
              y: record.value
            }));
            // Genera colori variabili per ciascun dataset
            const baseR = 50 + idx * 30,
                  baseG = 90,
                  baseB = 135;
            return {
              label: indicatorMapping[result.indicator] || result.indicator,
              data: dataPoints,
              backgroundColor: `rgba(${baseR}, ${baseG}, ${baseB}, 0.3)`,
              borderColor: `rgba(${baseR}, ${baseG}, ${baseB}, 1)`,
              borderWidth: 2,
              fill: true,
              tension: 0.2
            };
          });
          updateChart(datasets);
        })
        .catch(error => {
          console.error("Errore nel fetch:", error);
          alert("Errore nel recupero dei dati. Riprova.");
        });
    });
