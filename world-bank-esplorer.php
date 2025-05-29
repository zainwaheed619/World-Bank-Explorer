<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>World Bank Explorer | Bocaletto Luca</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Google Fonts per un design moderno -->
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <h1>World Bank Explorer</h1>
    <h2>Author: Bocaletto Luca</h2>
  </header>
  
  <div class="container">
    <!-- Scelta della scala dei dati (Data Scope) -->
    <div class="form-group">
      <label for="dataScope">Select Data Scope:</label>
      <select id="dataScope">
        <option value="country" selected>Country</option>
        <option value="continent">Continent/Region</option>
        <option value="world">World</option>
      </select>
    </div>
    
    <!-- Sezione selezione Paese (visibile se Data Scope = Country) -->
    <div class="form-group" id="countryGroup">
      <label for="countrySelect">Select Country:</label>
      <select id="countrySelect">
        <option value="USA">United States</option>
        <option value="CHN">China</option>
        <option value="IND">India</option>
        <option value="BRA">Brazil</option>
        <option value="RUS">Russia</option>
        <option value="ITA">Italy</option>
        <option value="DEU">Germany</option>
        <option value="FRA">France</option>
        <option value="JPN">Japan</option>
        <option value="GBR">United Kingdom</option>
        <option value="CAN">Canada</option>
        <option value="AUS">Australia</option>
        <option value="KOR">Republic of Korea</option>
        <option value="ESP">Spain</option>
        <option value="MEX">Mexico</option>
        <option value="ZAF">South Africa</option>
        <option value="NGA">Nigeria</option>
        <option value="EGY">Egypt</option>
        <option value="IDN">Indonesia</option>
        <option value="TUR">Turkey</option>
      </select>
    </div>
    
    <!-- Sezione selezione Region (visibile se Data Scope = Continent) -->
    <div class="form-group" id="continentGroup" style="display: none;">
      <label for="continentSelect">Select Region:</label>
      <select id="continentSelect">
        <option value="EAP">Asia &amp; Pacific</option>
        <option value="ECA">Europe</option>
        <option value="LCN">Latin America &amp; Caribbean</option>
        <option value="MNA">Middle East &amp; North Africa</option>
        <option value="NAC">North America</option>
        <option value="SAS">South Asia</option>
        <option value="SSA">Sub-Saharan Africa</option>
      </select>
    </div>
    
    <!-- Nel caso di World non serve ulteriore selezione -->
    
    <!-- Selezione degli Indicatori e Anni -->
    <div class="form-group">
      <label for="indicatorSelect">Select Indicators (Ctrl-click to choose multiple):</label>
      <select id="indicatorSelect" multiple></select>
    </div>
    <div class="form-group">
      <label for="startYear">Start Year:</label>
      <input type="number" id="startYear" value="2000" min="1960" max="2025">
    </div>
    <div class="form-group">
      <label for="endYear">End Year:</label>
      <input type="number" id="endYear" value="2020" min="1960" max="2025">
    </div>
    <button id="loadDataButton">Load Data</button>
    
    <hr>
    
    <!-- Sezione di Visualizzazione: Grafico a Linee con Chart.js -->
    <div class="chart-section">
      <h2>Time Series Chart (Chart.js)</h2>
      <canvas id="chartCanvas"></canvas>
    </div>
  </div>
  
  <footer>
    &copy; 2025 Bocaletto Luca – Data from World Bank Open Data
  </footer>
  
  <!-- Inclusione di Chart.js tramite CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
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
  </script>
  
  <!-- (Eventuale script per generare dinamicamente il dropdown continente se non presente)
       Qui abbiamo già inserito l'HTML per il dropdown nel blocco continentGroup,
       con le seguenti opzioni friendly:
         - Asia & Pacific (EAP)
         - Europe (ECA)
         - Latin America & Caribbean (LCN)
         - Middle East & North Africa (MNA)
         - North America (NAC)
         - South Asia (SAS)
         - Sub-Saharan Africa (SSA)
  -->
</body>
</html>
