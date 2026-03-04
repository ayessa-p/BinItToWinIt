<?php
require_once 'config/config.php';

$page_title = 'About Us';

// Get officers from database
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM org_officers WHERE is_active = 1 ORDER BY display_order ASC");
$officers = $stmt->fetchAll();

// Group officers by position level for better organization
$president = array_filter($officers, fn($o) => str_contains($o['position'], 'Chief Executive President'));
$chief_vp = array_filter($officers, fn($o) => str_contains($o['position'], 'Chief Executive Vice President'));
$other_vps = array_filter($officers, fn($o) => (str_contains($o['position'], 'Chief Executive VP') || str_contains($o['position'], 'CEVP')) && !str_contains($o['position'], 'Chief Executive Vice President'));
$executive_officers = array_filter($officers, fn($o) => (str_contains($o['position'], 'CEO') || str_contains($o['position'], 'Executive Officer')) && !str_contains($o['position'], 'Asst.'));
$assistant_officers = array_filter($officers, fn($o) => str_contains($o['position'], 'AVP') || str_contains($o['position'], 'Asst.'));
$adviser = array_filter($officers, fn($o) => str_contains(strtolower($o['position']), 'adviser'));

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

            <?php if (!empty($officers)): ?>
                <!-- Dynamic organizational chart -->
                <div style="display: flex; flex-direction: column; align-items: center; gap: 2rem;">
                    <!-- Adviser -->
                    <?php if (!empty($adviser) || true): ?>
                        <div style="display:flex; justify-content:center; width:100%;">
                            <?php if (!empty($adviser)): ?>
                                <?php foreach ($adviser as $officer): ?>
                                    <div style="min-width: 260px; text-align: center; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.06); border: 1px solid var(--azure-blue);">
                                        <?php if ($officer['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($officer['profile_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($officer['full_name']); ?>"
                                                 style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; margin-bottom: 0.5rem;">
                                        <?php endif; ?>
                                        <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;"><?php echo htmlspecialchars($officer['position']); ?></div>
                                        <div style="color: var(--dark-gray);"><?php echo htmlspecialchars($officer['full_name']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="min-width: 260px; text-align: center; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.06); border: 1px solid var(--azure-blue);">
                                    <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">MTICS Adviser</div>
                                    <div style="color: var(--dark-gray);">Prof. Pops V. Madriaga</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="width: 2px; height: 24px; background: var(--azure-blue);"></div>
                    <?php endif; ?>
                    <!-- Level 1: President -->
                    <?php if (!empty($president)): ?>
                        <div style="display: flex; justify-content: center; width: 100%;">
                            <?php foreach ($president as $officer): ?>
                                <div style="min-width: 260px; text-align: center; padding: 1rem 1.5rem; border-radius: 8px; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.06); border: 1px solid var(--azure-blue);">
                                    <?php if ($officer['profile_image']): ?>
                                        <img src="<?php echo htmlspecialchars($officer['profile_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($officer['full_name']); ?>"
                                             style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 0.5rem;">
                                    <?php endif; ?>
                                    <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;"><?php echo htmlspecialchars($officer['position']); ?></div>
                                    <div style="color: var(--dark-gray);"><?php echo htmlspecialchars($officer['full_name']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Connector -->
                        <div style="width: 2px; height: 24px; background: var(--azure-blue);"></div>
                    <?php endif; ?>

                    <!-- Level 2: Chief Executive Vice President -->
                    <?php if (!empty($chief_vp)): ?>
                        <div style="display: flex; justify-content: center; width: 100%;">
                            <?php foreach ($chief_vp as $officer): ?>
                                <div style="min-width: 260px; text-align: center; padding: 1rem 1.5rem; border-radius: 8px; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.06); border: 1px solid var(--azure-blue);">
                                    <?php if ($officer['profile_image']): ?>
                                        <img src="<?php echo htmlspecialchars($officer['profile_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($officer['full_name']); ?>"
                                             style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 0.5rem;">
                                    <?php endif; ?>
                                    <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;"><?php echo htmlspecialchars($officer['position']); ?></div>
                                    <div style="color: var(--dark-gray);"><?php echo htmlspecialchars($officer['full_name']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Connector -->
                        <div style="width: 2px; height: 24px; background: var(--azure-blue);"></div>
                    <?php endif; ?>

                    <!-- Level 3: Other Vice Presidents -->
                    <?php if (!empty($other_vps)): ?>
                        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1.5rem; width: 100%;">
                            <?php foreach ($other_vps as $officer): ?>
                                <div style="min-width: 220px; max-width: 260px; text-align: center; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
                                    <?php if ($officer['profile_image']): ?>
                                        <img src="<?php echo htmlspecialchars($officer['profile_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($officer['full_name']); ?>"
                                             style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 0.5rem;">
                                    <?php endif; ?>
                                    <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;"><?php echo htmlspecialchars($officer['position']); ?></div>
                                    <div style="color: var(--dark-gray);"><?php echo htmlspecialchars($officer['full_name']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Connector -->
                        <div style="width: 60%; max-width: 640px; border-top: 2px solid var(--azure-blue);"></div>
                    <?php endif; ?>

                    <!-- Level 3: Executive Officers -->
                    <?php if (!empty($executive_officers)): ?>
                        <div style="width: 100%;">
                            <h3 style="text-align: center; margin-bottom: 1rem; color: var(--text-dark);">Executive Officers</h3>
                            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem;">
                                <?php foreach ($executive_officers as $officer): ?>
                                    <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #ffffff; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
                                        <?php if ($officer['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($officer['profile_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($officer['full_name']); ?>"
                                                 style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 0.5rem;">
                                        <?php endif; ?>
                                        <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;"><?php echo htmlspecialchars($officer['position']); ?></div>
                                        <div style="color: var(--dark-gray);"><?php echo htmlspecialchars($officer['full_name']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Connector -->
                        <div style="width: 60%; max-width: 640px; border-top: 2px dashed var(--azure-blue); margin-top: 0.5rem;"></div>
                    <?php endif; ?>

                    <!-- Level 4: Assistant Officers -->
                    <?php if (!empty($assistant_officers)): ?>
                        <div style="width: 100%;">
                            <h3 style="text-align: center; margin: 1.5rem 0 1rem; color: var(--text-dark);">Assistant Officers</h3>
                            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem;">
                                <?php foreach ($assistant_officers as $officer): ?>
                                    <div style="flex: 1 1 220px; max-width: 260px; padding: 0.75rem 1rem; border-radius: 8px; background: #f9fafb;">
                                        <?php if ($officer['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($officer['profile_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($officer['full_name']); ?>"
                                                 style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 0.5rem;">
                                        <?php endif; ?>
                                        <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;"><?php echo htmlspecialchars($officer['position']); ?></div>
                                        <div style="color: var(--dark-gray);"><?php echo htmlspecialchars($officer['full_name']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--medium-gray);">
                    <i class="fa-solid fa-users" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p>No officers currently listed. Please check back later.</p>
                </div>
            <?php endif; ?>
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
