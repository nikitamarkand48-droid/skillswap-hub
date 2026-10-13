    </div><!-- /.container -->
</main>

<footer class="bg-white border-top py-4 mt-auto">
    <div class="container text-center text-muted small">
        <div class="row align-items-center">
            <div class="col-md-6 text-md-start mb-2 mb-md-0">
                <span class="fw-semibold text-dark">SkillSwap</span> &copy; <?= date('Y'); ?> — College Peer-to-Peer Skill Exchange Platform.
            </div>
            <div class="col-md-6 text-md-end">
                <span class="badge bg-light text-secondary border me-1"><i class="bi bi-code-slash"></i> PHP & MySQL</span>
                <span class="badge bg-light text-secondary border me-1"><i class="bi bi-bootstrap"></i> Bootstrap 5</span>
                <span class="badge bg-light text-secondary border me-1"><i class="bi bi-robot"></i> Gemini AI</span>
                <span class="badge bg-light text-secondary border"><i class="bi bi-pie-chart"></i> Chart.js</span>
            </div>
        </div>
    </div>
</footer>

<!-- ========================================== -->
<!-- AI Chatbot Floating Widget -->
<!-- ========================================== -->
<button class="chatbot-toggler" id="chatbotToggler" title="Ask Campus AI Advisor">
    <i class="bi bi-chat-dots-fill"></i>
</button>

<div class="chatbot-panel" id="chatbotPanel">
    <div class="chatbot-header">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-white text-primary rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                <i class="bi bi-robot fs-6"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold">SwapBot AI Advisor</h6>
                <small class="opacity-75" style="font-size: 0.72rem;">Online • Campus Skill AI</small>
            </div>
        </div>
        <button class="btn btn-sm text-white p-0" id="chatbotClose" style="font-size: 1.25rem;">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="chatbot-messages" id="chatbotMessages">
        <div class="chat-bubble bot">
            👋 Hi! I'm <strong>SwapBot</strong>, your AI skill exchange advisor. How can I help you learn or teach today?
        </div>
    </div>

    <div class="quick-prompts">
        <button type="button" class="quick-prompt-btn" data-prompt="How does skill matching work?">⚡ How to Swap?</button>
        <button type="button" class="quick-prompt-btn" data-prompt="I want to learn Python, what should I swap?">🐍 Python Advice</button>
        <button type="button" class="quick-prompt-btn" data-prompt="What are good tips for proposing a swap?">💡 Tips</button>
    </div>

    <form class="chatbot-input-bar" id="chatbotForm">
        <input type="text" class="form-control form-control-sm border-0 bg-light" id="chatbotInput" placeholder="Ask AI anything..." autocomplete="off" required>
        <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3">
            <i class="bi bi-send-fill"></i>
        </button>
    </form>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Chatbot Script -->
<script src="<?= app_path('assets/js/chatbot.js'); ?>"></script>
<!-- Main Application Script -->
<script src="<?= app_path('assets/js/main.js'); ?>"></script>
</body>
</html>
