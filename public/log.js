function loadData() {
    getRequest('/getLastLogData').then(response=> {
//element mit id logdata finden
        let lastLogDataContainer = document.getElementById('lastLogData')
        lastLogDataContainer.innerHTML = response
    })
}


document.addEventListener("DOMContentLoaded", function () {
    //jede 10 sekunden
    setInterval(loadData, 2000)
});