<?php
// Simple Appointment Calendar - No frameworks
// Serves the main HTML page and static assets; API is in api.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Appointment Calendar</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <header class="topbar">
        <div class="brand">Appointment Calendar</div>
        <nav class="nav">
            <button id="prevMonthBtn" aria-label="Previous month">◀</button>
            <div id="currentMonthLabel" class="month-label"></div>
            <button id="nextMonthBtn" aria-label="Next month">▶</button>
            <button id="todayBtn" class="today">Today</button>
        </nav>
    </header>

    <main class="container">
        <section class="calendar">
            <div class="weekdays">
                <div>Sun</div>
                <div>Mon</div>
                <div>Tue</div>
                <div>Wed</div>
                <div>Thu</div>
                <div>Fri</div>
                <div>Sat</div>
            </div>
            <div id="calendarGrid" class="grid" aria-live="polite"></div>
        </section>
    </main>

    <div id="dayModal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-dialog">
            <header class="modal-header">
                <div class="modal-title">
                    <span id="modalDateLabel"></span>
                </div>
                <button id="closeModalBtn" class="icon-btn" aria-label="Close">✕</button>
            </header>
            <section class="modal-body">
                <form id="addApptForm" class="appt-form">
                    <div class="field-row">
                        <label>Time
                            <input type="time" id="apptTime" required>
                        </label>
                        <label>Duration (min)
                            <input type="number" id="apptDuration" min="5" step="5" value="30" required>
                        </label>
                    </div>
                    <label>Title
                        <input type="text" id="apptTitle" placeholder="Meeting with..." required>
                    </label>
                    <label>Notes
                        <textarea id="apptNotes" rows="2" placeholder="Optional"></textarea>
                    </label>
                    <div class="actions">
                        <button type="submit" class="primary">Add Appointment</button>
                    </div>
                </form>
                <div class="appt-list">
                    <h3>Appointments</h3>
                    <ul id="apptList"></ul>
                </div>
            </section>
        </div>
    </div>

    <template id="apptItemTemplate">
        <li class="appt-item">
            <div class="time"></div>
            <div class="title"></div>
            <button class="delete-btn" title="Delete">🗑</button>
        </li>
    </template>

    <footer class="footer">
        <div>Data stored locally in JSON (no DB). For demo use.</div>
    </footer>

    <script>window.API_BASE = 'api.php';</script>
    <script src="assets/app.js"></script>
</body>
</html>

