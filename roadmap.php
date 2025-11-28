<?php
session_start();
$page_title = "LibraFlow - Development Team";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-area">
            <!-- Development Team Section -->
            <div class="team-hero">
                <div class="hero-content">
                    <h1>Meet Our Development Team</h1>
                    <p>Passionate developers creating innovative solutions</p>
                </div>
            </div>

            <!-- Team Slider -->
            <div class="team-slider-section">
                <div class="slider-container">
                    <!-- Developer 1 -->
                    <div class="dev-card">
                        <div class="dev-image">
                            <img src="images/dev1.jpg" alt="John Davis" width="150" height="150" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iIzNCOEUyRiIvPjx0ZXh0IHg9Ijc1IiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSI+TEQ8L3RleHQ+PC9zdmc+'">
                            <div class="image-overlay"></div>
                        </div>
                        <div class="dev-content">
                            <h3>John Davis</h3>
                            <p class="role">Lead Developer</p>
                            <div class="contact">
                                <i class="fas fa-envelope"></i>
                                <span>john@libraflow.dev</span>
                            </div>
                            <div class="skills">
                                <span>PHP</span>
                                <span>MySQL</span>
                                <span>Architecture</span>
                            </div>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-github"></i></a>
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="mailto:john@libraflow.dev"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Developer 2 -->
                    <div class="dev-card">
                        <div class="dev-image">
                            <img src="images/dev2.jpg" alt="Sarah Miller" width="150" height="150" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iIzEwQjk4MSIvPjx0ZXh0IHg9Ijc1IiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSI+RkQ8L3RleHQ+PC9zdmc+'">
                            <div class="image-overlay"></div>
                        </div>
                        <div class="dev-content">
                            <h3>Sarah Miller</h3>
                            <p class="role">Frontend Developer</p>
                            <div class="contact">
                                <i class="fas fa-envelope"></i>
                                <span>sarah@libraflow.dev</span>
                            </div>
                            <div class="skills">
                                <span>React</span>
                                <span>CSS3</span>
                                <span>JavaScript</span>
                            </div>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-github"></i></a>
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="mailto:sarah@libraflow.dev"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Developer 3 -->
                    <div class="dev-card">
                        <div class="dev-image">
                            <img src="images/dev3.jpg" alt="Brian Roberts" width="150" height="150" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI0E4NTVGNyIvPjx0ZXh0IHg9Ijc1IiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSI+QkQ8L3RleHQ+PC9zdmc+'">
                            <div class="image-overlay"></div>
                        </div>
                        <div class="dev-content">
                            <h3>Brian Roberts</h3>
                            <p class="role">Backend Developer</p>
                            <div class="contact">
                                <i class="fas fa-envelope"></i>
                                <span>brian@libraflow.dev</span>
                            </div>
                            <div class="skills">
                                <span>Node.js</span>
                                <span>Python</span>
                                <span>API</span>
                            </div>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-github"></i></a>
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="mailto:brian@libraflow.dev"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Developer 4 -->
                    <div class="dev-card">
                        <div class="dev-image">
                            <img src="images/dev4.jpg" alt="Alex Lee" width="150" height="150" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI0Y1OUUwQiIvPjx0ZXh0IHg9Ijc1IiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSI+RlM8L3RleHQ+PC9zdmc+'">
                            <div class="image-overlay"></div>
                        </div>
                        <div class="dev-content">
                            <h3>Alex Lee</h3>
                            <p class="role">Full Stack Developer</p>
                            <div class="contact">
                                <i class="fas fa-envelope"></i>
                                <span>alex@libraflow.dev</span>
                            </div>
                            <div class="skills">
                                <span>MERN</span>
                                <span>PHP</span>
                                <span>DevOps</span>
                            </div>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-github"></i></a>
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="mailto:alex@libraflow.dev"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Developer 5 -->
                    <div class="dev-card">
                        <div class="dev-image">
                            <img src="images/dev5.jpg" alt="Maya Kumar" width="150" height="150" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI0Y0M0Y1RSIvPjx0ZXh0IHg9Ijc1IiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSI+VUk8L3RleHQ+PC9zdmc+'">
                            <div class="image-overlay"></div>
                        </div>
                        <div class="dev-content">
                            <h3>Maya Kumar</h3>
                            <p class="role">UI/UX Designer</p>
                            <div class="contact">
                                <i class="fas fa-envelope"></i>
                                <span>maya@libraflow.dev</span>
                            </div>
                            <div class="skills">
                                <span>Figma</span>
                                <span>Adobe XD</span>
                                <span>Prototyping</span>
                            </div>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-github"></i></a>
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="mailto:maya@libraflow.dev"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Developer 6 -->
                    <div class="dev-card">
                        <div class="dev-image">
                            <img src="images/dev6.jpg" alt="David Brown" width="150" height="150" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iIzA2QjZENCIvPjx0ZXh0IHg9Ijc1IiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSI+REI8L3RleHQ+PC9zdmc+'">
                            <div class="image-overlay"></div>
                        </div>
                        <div class="dev-content">
                            <h3>David Brown</h3>
                            <p class="role">Database Architect</p>
                            <div class="contact">
                                <i class="fas fa-envelope"></i>
                                <span>david@libraflow.dev</span>
                            </div>
                            <div class="skills">
                                <span>MySQL</span>
                                <span>MongoDB</span>
                                <span>Optimization</span>
                            </div>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-github"></i></a>
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="mailto:david@libraflow.dev"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Stats -->
            <div class="team-stats">
                <div class="stat-item">
                    <div class="stat-number">6</div>
                    <div class="stat-label">Developers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">50+</div>
                    <div class="stat-label">Projects</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">5+</div>
                    <div class="stat-label">Years Experience</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Dedicated</div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Simple horizontal scroll for team slider
        document.addEventListener('DOMContentLoaded', function() {
            const slider = document.querySelector('.slider-container');
            let isDown = false;
            let startX;
            let scrollLeft;

            slider.addEventListener('mousedown', (e) => {
                isDown = true;
                startX = e.pageX - slider.offsetLeft;
                scrollLeft = slider.scrollLeft;
            });

            slider.addEventListener('mouseleave', () => {
                isDown = false;
            });

            slider.addEventListener('mouseup', () => {
                isDown = false;
            });

            slider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - slider.offsetLeft;
                const walk = (x - startX) * 2;
                slider.scrollLeft = scrollLeft - walk;
            });
        });
    </script>
</body>
</html>