<?php
require_once 'config/config.php';

$page_title = 'About Us';
include 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">About MTICS</h1>
        
        <div style="max-width: 900px; margin: 0 auto 3rem;">
            <h2 style="font-size: 2rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-dark);">Who We Are</h2>
            <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem; color: var(--dark-gray);">
                The Manila Technician Institute Computer Society (MTICS) is a student-led 
                organization dedicated to advancing computer science education, fostering 
                technological innovation, and promoting environmental sustainability within 
                our academic community.
            </p>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--dark-gray);">
                Founded with the vision of bridging the gap between theoretical knowledge 
                and practical application, MTICS provides a platform for students to 
                explore cutting-edge technologies, collaborate on meaningful projects, 
                and make a positive impact on both the digital and physical world.
            </p>
        </div>
        
        <div class="grid grid-2" style="margin-bottom: 3rem;">
            <div class="card">
                <h3 class="card-title" style="margin-bottom: var(--spacing-sm);">Our Mission</h3>
                <p class="card-body" style="margin: 0;">The Manila Technician Institute Computer Society intends to
                    protect the individual and holistic rights and interest of its 
                    members, enhance electrical and technical skills in field of 
                    computer literacy and information technology through the 
                    development of holistic amity and leadership.
                    Aims to implant among ourselves moral values and apt 
                    conduct and cultivate mutual concern, and carting upon the
                    member through the guidance and acceptance of our 
                    predecessors in our respective technology, the Information 
                    Technology and Computer Engineering Technology.
                </p>
            </div>
            
            <div class="card">
                <h3 class="card-title" style="margin-bottom: var(--spacing-sm);">Our Vision</h3>
                <p class="card-body" style="margin: 0;">The Manila Technician Institute Computer Society
                    envisages to produce highly competitive and
                    informative computer technicians, technically
                    equipped and proficiently literate that can bequeath
                    assistance, develop and uphold the furtherance of 
                    Technological didactic progression of the country and 
                    develop well skilled and morally literate leaders that 
                    are capable of providing solutions and alternatively 
                    response to tribulations and states of affairs in a 
                    practical and tactical advance.
                </p>
            </div>
        </div>
        
        <h2 class="section-title" style="margin-top: 4rem;">MTICS Officers - Academic Year 2025-2026</h2>
        <div style="max-width: 1000px; margin: 3rem auto;">
            <p style="text-align: center; color: var(--dark-gray); font-size: 1.1rem; margin-bottom: 2rem;">
                Organizational chart of MTICS officers for the current academic year.
            </p>

            <!-- Simple organizational chart -->
            <div style="display: flex; flex-direction: column; align-items: center; gap: 2rem;">
                <!-- Level 1: President -->
                <div style="display: flex; justify-content: center; width: 100%;">
                    <div style="min-width: 260px; text-align: center; padding: 1rem 1.5rem; border-radius: 8px; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.06); border: 1px solid var(--azure-blue);">
                        <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Chief Executive President</div>
                        <div style="color: var(--dark-gray);">Jerome Steven Rosario</div>
                    </div>
                </div>

                <!-- Connector -->
                <div style="width: 2px; height: 24px; background: var(--azure-blue);"></div>

                <!-- Level 2: Vice Presidents -->
                <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1.5rem; width: 100%;">
                    <div style="min-width: 220px; max-width: 260px; text-align: center; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
                        <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Chief Executive Vice President</div>
                        <div style="color: var(--dark-gray);">Hanna Clerdee Cruz</div>
                    </div>
                    <div style="min-width: 220px; max-width: 260px; text-align: center; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
                        <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">CEVP for Internal Affairs</div>
                        <div style="color: var(--dark-gray);">Ianzae Ryan Ego</div>
                    </div>
                    <div style="min-width: 220px; max-width: 260px; text-align: center; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
                        <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">CEVP for External Affairs</div>
                        <div style="color: var(--dark-gray);">Sachzie Sofia Ilagan</div>
                    </div>
                </div>

                <!-- Connector -->
                <div style="width: 60%; max-width: 640px; border-top: 2px solid var(--azure-blue);"></div>

                <!-- Level 3: Executive Officers -->
                <div style="width: 100%;">
                    <h3 style="text-align: center; margin-bottom: 1rem; color: var(--text-dark);">Executive Officers</h3>
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem;">
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">CEO for Documentation</div>
                            <div style="color: var(--dark-gray);">Cristel Kate Famini</div>
                        </div>
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">CEO for Finance</div>
                            <div style="color: var(--dark-gray);">Kimberly Eledia</div>
                        </div>
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">CEO for Audit</div>
                            <div style="color: var(--dark-gray);">Mary Pauline Calungsod</div>
                        </div>
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">CEO for Information</div>
                            <div style="color: var(--dark-gray);">Lord Cedric Vila</div>
                        </div>
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">CEO for Activities & Programs</div>
                            <div style="color: var(--dark-gray);">Kim Jensen Yebes</div>
                        </div>
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">CEO for Logistics</div>
                            <div style="color: var(--dark-gray);">Krsmur Chelvin Lacorte</div>
                        </div>
                    </div>
                </div>

                <!-- Connector -->
                <div style="width: 60%; max-width: 640px; border-top: 2px dashed var(--azure-blue); margin-top: 0.5rem;"></div>

                <!-- Level 4: Assistant Officers -->
                <div style="width: 100%;">
                    <h3 style="text-align: center; margin: 1.5rem 0 1rem; color: var(--text-dark);">Assistant Officers</h3>
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem;">
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #f9fafb;">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">AVP for Internal Affairs</div>
                            <div style="color: var(--dark-gray);">Ayessa Denisse Pili</div>
                        </div>
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #f9fafb;">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">AVP for External Affairs</div>
                            <div style="color: var(--dark-gray);">Dion Ongaria</div>
                        </div>
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #f9fafb;">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Asst. EO for Documentation</div>
                            <div style="color: var(--dark-gray);">Lance Grant Haboc</div>
                        </div>
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #f9fafb;">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Asst. EO for Finance</div>
                            <div style="color: var(--dark-gray);">Elijah Neil Gallardo</div>
                        </div>
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #f9fafb;">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Asst. EO for Audit</div>
                            <div style="color: var(--dark-gray);">Julia Faye Datang</div>
                        </div>
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #f9fafb;">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Asst. EO for Information</div>
                            <div style="color: var(--dark-gray);">Trisha Mia Morales</div>
                        </div>
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #f9fafb;">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Asst. EO for Activities & Programs</div>
                            <div style="color: var(--dark-gray);">John Regan Asino</div>
                        </div>
                        <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #f9fafb;">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Asst. EO for Logistics</div>
                            <div style="color: var(--dark-gray);">Marcus Iñigo Aristain</div>
                        </div>
                    </div>
                </div>
            </div>
        
        <div style="text-align: center; margin-top: 3rem;">
            <a href="contact.php" class="btn btn-primary btn-large">Contact Us</a>
            <?php if (!is_logged_in()): ?>
                <a href="auth/register.php" class="btn btn-secondary btn-large" style="margin-left: 1rem;">Join MTICS</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
