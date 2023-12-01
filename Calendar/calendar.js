//Variable Declarations
let currentDate = new Date();
let events = null;
let isLoggedIn = false;
let eventInvitees = [];

//generates the calendar for a given month
function renderCalendar(date, events) {
    const monthDays = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
    const firstDayIndex = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
    let days = "";

    for (let i = 0; i < firstDayIndex; i++) {
        days += "<div></div>"; // Empty days before the start of the month
    }
   
    for (let i = 1; i <= monthDays; i++) {
        if (isLoggedIn){
            let datestr_null = "";
            let datestr = datestr_null.concat(date.getFullYear(), "-", date.getMonth()+1, "-", i);
            days += `<div onclick = "showAddEventForm('${datestr}')">${i}`;
        }
        else{
            days += `<div>${i}`;
        }
        if (events != null){
            for (let index in events){
                let event_date = events[index].event_date.split("-");
                if (event_date[0] == date.getFullYear() &&
                    event_date[1] == date.getMonth()+1 &&
                    event_date[2] == i){
                        if (sessionStorage.getItem('show_cate') == 'true'){
                            days += `<br><p id="event-${events[index].category}" onclick = "event.stopPropagation(); showEditEventForm(${events[index].event_id}, '${events[index].event_title}', '${events[index].event_time}')">${events[index].event_title}<br>time: ${events[index].event_time}</p>`;
                        }
                        else{
                            days += `<br><p id="event-default" onclick = "event.stopPropagation(); showEditEventForm(${events[index].event_id}, '${events[index].event_title}', '${events[index].event_time}')">${events[index].event_title}<br>time: ${events[index].event_time}</p>`;
                        }
                        
                }
            }
        }
        days += `</div>`;
    }
    let btn = "";
    if (isLoggedIn){
        if (sessionStorage.getItem('show_cate') == 'true'){
            btn = `<button onclick='update_showCate()'>hide category</button>`;
        }
        else{
            btn = `<button onclick='update_showCate()'>show category</button>`;
        }
    }

    document.getElementById('calendarDays').innerHTML = days;
    document.getElementById('currentMonth').textContent = date.toLocaleString('default', { month: 'long' }) + " " + date.getFullYear();
    document.getElementById('category_btn').innerHTML = btn;
}

function update_showCate(){
    if (sessionStorage.getItem('show_cate') == 'true'){
        sessionStorage.setItem('show_cate', 'false');
    }
    else{
        sessionStorage.setItem('show_cate', 'true');
    }
    get_Events();
}

//update the currentDate to navigate to the previous or next month
function prevMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    get_Events();
}

function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    get_Events();
}

//display field for user registration and login
function sign_up(){
    const signup_div = document.getElementsByClassName("signin_up")[0];
    let content = "<label>Username: </label> <input type='text' id='username'><br><label>Password: </label> <input type='password' id='password'><br><label>Confirm password: </label><input type='password' id='confirm_pass'><br><br><button onclick='sign_upRequest()'>SignUp</button>";
    signup_div.innerHTML = content;
}

function log_in(){
    const signup_div = document.getElementsByClassName("signin_up")[0];
    let content = "<label>Username: </label> <input type='text' id='username'><br><label>Password: </label> <input type='password' id='password'><br><br><button onclick='log_inRequest()'>Log_in</button>";
    signup_div.innerHTML = content;
}

function sign_upRequest(){
    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;
    const com_pass = document.getElementById("confirm_pass").value;
    const data = { 'username': username, 'password': password,  'confirm_pass': com_pass};

    fetch("signup.php", {
        method: 'POST',
        body: JSON.stringify(data),
        headers: { 'content-type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        console.log(data.success ? "You've been signed up!" : `You were not signed up because ${data.message}`);
        if(data.success) {
            const signup_div = document.getElementsByClassName("signin_up")[0];
            let content = "<label>Username: </label> <input type='text' id='username'><br><label>Password: </label> <input type='password' id='password'><br><br><button onclick='log_inRequest()'>Log_in</button>";
            signup_div.innerHTML = content;
        }
    })
    .catch(err => console.error(err));
}

function log_inRequest(){
    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;
    const data = { 'username': username, 'password': password };

    fetch("login.php", {
            method: 'POST',
            body: JSON.stringify(data),
            headers: { 'content-type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            token = data.message;
            sessionStorage.setItem('token', token);
            sessionStorage.setItem('show_cate', 'false');
        } else {
            console.error(data.message);
        }
        checkLoginStatus();
    })
    .catch(err => console.error(err));
}

//checks the login status of the user and updates the UI
function checkLoginStatus() {
    fetch('check_login_status.php', {method: 'POST'})
    .then(response => response.json())
    .then(data => {
        const addEventButton = document.getElementById('addEventButton');

        if (data.status === 'logged_in') {
            isLoggedIn = true;
            const username = data.username;
            document.getElementById("user_title").innerText = "Welcome " + username;
            console.log("User is logged in as", data.username);
            const signup_div = document.getElementsByClassName("signin_up")[0];
            let content = "<button onclick='log_outRequest()'>Log_out</button>";
            signup_div.innerHTML = content;
            addEventButton.style.display = "block";  // Show "Add Event" button
            get_Events();
        } else {
            isLoggedIn = false;
            console.log("User is not logged in");
            document.getElementById("user_title").innerText = "Simple Calendar";
            renderCalendar(currentDate, null);
            addEventButton.style.display = "none";  // Hide "Add Event" button
        }
    })
    .catch(err => console.error(err));
}

window.onload = function() {
    checkLoginStatus();
}

//sends a logout request
function log_outRequest() {
    fetch('logout.php', {method: 'POST'})
    .then(response => response.json())
    .then(data => {
        if (data.status === 'logged_out') {
            isLoggedIn = false;
            console.log("User is logged out");
            const signup_div = document.getElementsByClassName("signin_up")[0];
            let content = "<button onclick='sign_up()'>SignUp</button> <button onclick='log_in()'>Log_in</button>";
            signup_div.innerHTML = content;
            checkLoginStatus();
        }})
}

function get_Events(){
    fetch('get_event.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.error){
            renderCalendar(currentDate, data);
        }
        else{
            renderCalendar(currentDate, null);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

//functions show modals for adding or editing events
function showAddEventForm(dateString) {
    const modal = document.getElementById("myModal");
    modal.style.display = "block";
    const addEventButton = document.getElementById("addEventButton");
    addEventButton.onclick = function() {
        const eventTitle = document.getElementById("eventTitle").value;
        const eventTime = document.getElementById("eventTime").value;
        const eventInvitees_raw = document.getElementById("eventInvitees").value;
        const category = document.getElementById("category_add").value;
        if (eventInvitees_raw != null || eventInvitees_raw != ""){
            eventInvitees = eventInvitees_raw.split(",").map(user => user.trim());
        }
        else{
            eventInvitees = "";
        }
        const data = {
            'token': sessionStorage.getItem('token'),
            'event_title': eventTitle,
            'event_date': dateString,
            'event_time': eventTime,
            'event_user': eventInvitees,
            'category': category
        };
        
        fetch("create_event.php", {
            method: 'POST',
            body: JSON.stringify(data),
            headers: { 'content-type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh calendar to reflect the added event
                get_Events();
            }
            else{
                console.log(data.message);
            }
        })
        modal.style.display = "none";
    };
}

function showEditEventForm(id, title, time) {
    const modal = document.getElementById("myModal2");
    modal.style.display = "block";
    const editEventButton = document.getElementById("editEventButton");
    const deleteEventButton = document.getElementById("deleteEventButton");
    const eventTitle = document.getElementById("eventTitle_edit");
    const eventTime = document.getElementById("eventTime_edit");
    eventTitle.textContent = title;
    eventTime.textContent = time;
    editEventButton.onclick = function() {
        const eventTitle = document.getElementById("eventTitle_edit").value;
        const eventTime = document.getElementById("eventTime_edit").value;
        const category = document.getElementById("category_edit").value;
        const data = {
            'token': sessionStorage.getItem('token'),
            'event_id': id,
            'event_title': eventTitle,
            'event_time': eventTime,
            'category': category
        };
        fetch("edit_event.php", {
            method: 'POST',
            body: JSON.stringify(data),
            headers: { 'content-type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh calendar to reflect the added event
                get_Events();
            }
            else{
                console.log(data.message);
            }
        })
        modal.style.display = "none";
    };

    deleteEventButton.onclick = function(){
        const data = {'token': sessionStorage.getItem('token'), 'event_id': id};
        fetch("delete_event.php", {
            method: 'POST',
            body: JSON.stringify(data),
            headers: { 'content-type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success){
                get_Events();
            }
            else{
                console.log(data.message);
            }
        })
        modal.style.display = "none";
    };
}

const modal = document.getElementById('myModal');
const span = document.getElementsByClassName('close')[0];

span.onclick = function() {
    modal.style.display = "none";
}

const modal2 = document.getElementById('myModal2');
const span2 = document.getElementsByClassName('close')[1];

span2.onclick = function() {
    modal2.style.display = "none";
}

window.onclick = function(event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
    if (event.target === modal2) {
        modal2.style.display = "none";
    }
}

// periodically check for upcoming events and display alerts
let delay = 500;
let event_alert;
function updateTimeNow() {
    let timeNow = Date.now();
    fetch('get_event.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.error){
            event_alert = data;
            for (let index in event_alert){
                let timestr = event_alert[index].event_date + " " + event_alert[index].event_time;
                time_to_alert = new Date(timestr).getTime() - 300000;
                let tolerance = 900;
                if (time_to_alert - timeNow < tolerance && time_to_alert - timeNow > 0){
                    alert(`Event: ${event_alert[index].event_title} will begin less than 5 min`);
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });

}

updateTimeNow();

const interval = setInterval(updateTimeNow, delay);