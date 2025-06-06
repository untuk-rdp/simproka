function initCharts() {
  // Chart program per divisi
  const divisiCtx = document.getElementById('divisiChart');
  if (divisiCtx) {
    new Chart(divisiCtx, {
      type: 'bar',
      data: {
        labels: JSON.parse(divisiCtx.dataset.labels),
        datasets: [{
          label: 'Jumlah Program',
          data: JSON.parse(divisiCtx.dataset.data),
          backgroundColor: '#4361ee',
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  }

  // Chart status program
  const statusCtx = document.getElementById('statusChart');
  if (statusCtx) {
    new Chart(statusCtx, {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Berjalan', 'Tertunda', 'Perencanaan'],
        datasets: [{
          data: JSON.parse(statusCtx.dataset.data),
          backgroundColor: [
            '#4cc9f0',
            '#4361ee',
            '#f72585',
            '#6c757d'
          ],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        },
        cutout: '70%'
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', initCharts);