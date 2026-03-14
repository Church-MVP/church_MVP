<?php
/**
 * Sermon Detail Page - sermon.php
 *
 * Displays the full details of a single sermon.
 * Receives the sermon ID via GET parameter: sermon.php?id=<sermon_id>
 *
 * Security: Input is validated and cast to integer before any DB use.
 */

// Include database connection
require_once 'includes/db.php';

// -------------------------------------------------------
// 1. VALIDATE INPUT
// -------------------------------------------------------
// Ensure 'id' exists, is numeric, and is a positive integer.
if (
    !isset($_GET['id']) ||
    !ctype_digit((string) $_GET['id']) ||
    (int) $_GET['id'] <= 0
) {
    // Invalid or missing ID — redirect to the sermons page.
    header('Location: services.php#sermons');
    exit;
}

$sermon_id = (int) $_GET['id'];

// -------------------------------------------------------
// 2. FETCH SERMON FROM DATABASE
// -------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM sermons WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $sermon_id]);
$sermon = $stmt->fetch();

// If no sermon was found, redirect gracefully.
if (!$sermon) {
    header('Location: services.php#sermons');
    exit;
}

// -------------------------------------------------------
// 3. FETCH RELATED SERMONS (same preacher or recent, excl. current)
// -------------------------------------------------------
$stmt_related = $pdo->prepare(
    "SELECT id, title, preacher, sermon_date, cover_image
     FROM sermons
     WHERE id != :id
     ORDER BY
         CASE WHEN preacher = :preacher THEN 0 ELSE 1 END,
         sermon_date DESC
     LIMIT 3"
);
$stmt_related->execute([':id' => $sermon_id, ':preacher' => $sermon['preacher']]);
$related_sermons = $stmt_related->fetchAll();

// -------------------------------------------------------
// 4. HELPERS & DERIVED VALUES
// -------------------------------------------------------
$page_title = htmlspecialchars($sermon['title']);

// Resolve sermon image
$sermon_image = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=900&h=500&fit=crop';
if (!empty($sermon['cover_image']) && file_exists($sermon['cover_image'])) {
    $sermon_image = htmlspecialchars($sermon['cover_image']);
}

// Format the sermon date using the site helper (defined in db.php / functions)
$formatted_date = function_exists('format_date')
    ? format_date($sermon['sermon_date'])
    : date('F j, Y', strtotime($sermon['sermon_date']));

// Include the site header
include 'includes/header.php';
?>

<!-- ============================================================
     SERMON HERO
     ============================================================ -->
<section class="sermon-hero" style="
    position: relative;
    min-height: 420px;
    display: flex;
    align-items: flex-end;
    overflow: hidden;
    background: var(--primary-dark);
">
    <!-- Background image with overlay -->
    <div style="
        position: absolute; inset: 0;
        background: url('<?php echo $sermon_image; ?>') center/cover no-repeat;
        filter: brightness(0.35);
        transform: scale(1.03);
        transition: transform 6s ease;
    " id="heroBackground"></div>

    <div class="container" style="position: relative; z-index: 2; padding-bottom: 3rem; padding-top: 6rem;">
        <!-- Breadcrumb -->
        <nav style="margin-bottom: 1.5rem;">
            <a href="index.php" style="color: rgba(255,255,255,0.65); text-decoration: none; font-size: 0.9rem;">Home</a>
            <span style="color: rgba(255,255,255,0.4); margin: 0 0.5rem;">/</span>
            <a href="services.php#sermons" style="color: rgba(255,255,255,0.65); text-decoration: none; font-size: 0.9rem;">Sermons</a>
            <span style="color: rgba(255,255,255,0.4); margin: 0 0.5rem;">/</span>
            <span style="color: white; font-size: 0.9rem;"><?php echo $page_title; ?></span>
        </nav>

        <!-- Title & meta -->
        <h1 style="color: white; font-size: clamp(1.8rem, 4vw, 3rem); line-height: 1.2; max-width: 800px; margin-bottom: 1.25rem;">
            <?php echo $page_title; ?>
        </h1>

        <div style="display: flex; flex-wrap: wrap; gap: 1.25rem; align-items: center;">
            <span style="color: rgba(255,255,255,0.85); font-size: 1rem;">
                <i class="fas fa-user" style="color: var(--primary-light); margin-right: 0.4rem;"></i>
                <?php echo htmlspecialchars($sermon['preacher']); ?>
            </span>
            <span style="color: rgba(255,255,255,0.85); font-size: 1rem;">
                <i class="fas fa-calendar" style="color: var(--primary-light); margin-right: 0.4rem;"></i>
                <?php echo $formatted_date; ?>
            </span>
            <?php if (!empty($sermon['scripture_reference'])): ?>
            <span style="color: rgba(255,255,255,0.85); font-size: 1rem;">
                <i class="fas fa-book-open" style="color: var(--primary-light); margin-right: 0.4rem;"></i>
                <?php echo htmlspecialchars($sermon['scripture_reference']); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Parallax subtle animation -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const bg = document.getElementById('heroBackground');
    if (bg) bg.style.transform = 'scale(1)';
});
</script>

<!-- ============================================================
     MAIN CONTENT
     ============================================================ -->
<section class="section" style="background: #f8f9fa;">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 340px; gap: 2.5rem; align-items: start;">

            <!-- LEFT: Sermon body -->
            <div>

                <!-- Media Players -->
                <?php if (!empty($sermon['video_url'])): ?>
                <div class="sermon-media-block" style="
                    background: white;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                    margin-bottom: 2rem;
                ">
                    <?php
                    // Attempt to embed YouTube / Vimeo; otherwise show a plain link button.
                    $video_url  = htmlspecialchars($sermon['video_url']);
                    $embed_url  = null;

                    // YouTube
                    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{11})/', $sermon['video_url'], $yt_matches)) {
                        $embed_url = 'https://www.youtube.com/embed/' . $yt_matches[1] . '?rel=0';
                    }
                    // Vimeo
                    elseif (preg_match('/vimeo\.com\/(\d+)/', $sermon['video_url'], $vm_matches)) {
                        $embed_url = 'https://player.vimeo.com/video/' . $vm_matches[1];
                    }
                    ?>

                    <?php if ($embed_url): ?>
                    <div style="position: relative; padding-bottom: 56.25%; height: 0;">
                        <iframe
                            src="<?php echo $embed_url; ?>"
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                            allowfullscreen
                            loading="lazy"
                            title="<?php echo $page_title; ?>">
                        </iframe>
                    </div>
                    <?php else: ?>
                    <div style="padding: 2rem; text-align: center;">
                        <i class="fas fa-play-circle" style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;"></i>
                        <p style="margin-bottom: 1rem; color: #555;">Watch this sermon on the original platform:</p>
                        <a href="<?php echo $video_url; ?>" target="_blank" rel="noopener" class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i> Watch Video
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Audio Player -->
                <?php if (!empty($sermon['audio_url'])): ?>
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 1.5rem;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                    margin-bottom: 2rem;
                ">
                    <h3 style="margin-bottom: 1rem; color: var(--primary-dark); font-size: 1.1rem;">
                        <i class="fas fa-headphones" style="color: var(--primary-light); margin-right: 0.5rem;"></i>
                        Listen to the Audio
                    </h3>
                    <audio controls style="width: 100%;">
                        <source src="<?php echo htmlspecialchars($sermon['audio_url']); ?>">
                        Your browser does not support audio playback.
                        <a href="<?php echo htmlspecialchars($sermon['audio_url']); ?>" class="btn btn-secondary">
                            <i class="fas fa-download"></i> Download Audio
                        </a>
                    </audio>
                </div>
                <?php endif; ?>

                <!-- Description / Full Message -->
                <?php if (!empty($sermon['description'])): ?>
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 2rem;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                    margin-bottom: 2rem;
                ">
                    <h2 style="color: var(--primary-dark); font-size: 1.4rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 2px solid #f0f0f0;">
                        <i class="fas fa-align-left" style="color: var(--primary-light); margin-right: 0.5rem;"></i>
                        Sermon Message
                    </h2>
                    <div style="color: #444; line-height: 1.9; font-size: 1.05rem;">
                        <?php echo nl2br(htmlspecialchars($sermon['description'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- No media at all fallback -->
                <?php if (empty($sermon['video_url']) && empty($sermon['audio_url']) && empty($sermon['description'])): ?>
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 3rem;
                    text-align: center;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                    margin-bottom: 2rem;
                ">
                    <i class="fas fa-clock" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <p style="color: #888; font-size: 1.1rem;">Full sermon content will be available soon. Check back later!</p>
                </div>
                <?php endif; ?>

                <!-- Navigation: Back / Next links (simple prev-next by date) -->
                <?php
                // Fetch previous (older) and next (newer) sermon IDs
                $stmt_prev = $pdo->prepare("SELECT id, title FROM sermons WHERE sermon_date < :date OR (sermon_date = :date2 AND id < :id) ORDER BY sermon_date DESC, id DESC LIMIT 1");
                $stmt_prev->execute([':date' => $sermon['sermon_date'], ':date2' => $sermon['sermon_date'], ':id' => $sermon_id]);
                $prev_sermon = $stmt_prev->fetch();

                $stmt_next = $pdo->prepare("SELECT id, title FROM sermons WHERE sermon_date > :date OR (sermon_date = :date2 AND id > :id) ORDER BY sermon_date ASC, id ASC LIMIT 1");
                $stmt_next->execute([':date' => $sermon['sermon_date'], ':date2' => $sermon['sermon_date'], ':id' => $sermon_id]);
                $next_sermon = $stmt_next->fetch();
                ?>
                <div style="
                    display: flex;
                    justify-content: space-between;
                    gap: 1rem;
                    flex-wrap: wrap;
                    margin-bottom: 2rem;
                ">
                    <?php if ($prev_sermon): ?>
                    <a href="sermon.php?id=<?php echo (int)$prev_sermon['id']; ?>" class="btn btn-secondary" style="flex: 1; min-width: 180px;">
                        <i class="fas fa-chevron-left"></i>
                        <span style="margin-left: 0.4rem; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; max-width: 200px; display: inline-block; vertical-align: middle;">
                            <?php echo htmlspecialchars($prev_sermon['title']); ?>
                        </span>
                    </a>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>

                    <?php if ($next_sermon): ?>
                    <a href="sermon.php?id=<?php echo (int)$next_sermon['id']; ?>" class="btn btn-secondary" style="flex: 1; min-width: 180px; text-align: right;">
                        <span style="margin-right: 0.4rem; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; max-width: 200px; display: inline-block; vertical-align: middle;">
                            <?php echo htmlspecialchars($next_sermon['title']); ?>
                        </span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>

            </div><!-- /LEFT -->

            <!-- RIGHT: Sidebar -->
            <aside>

                <!-- Sermon Info Card -->
                <div style="
                    background: white;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                    margin-bottom: 2rem;
                ">
                    <!-- Thumbnail (only if no embedded video above) -->
                    <?php if (empty($sermon['video_url'])): ?>
                    <img src="<?php echo $sermon_image; ?>"
                         alt="<?php echo $page_title; ?>"
                         style="width: 100%; height: 180px; object-fit: cover;">
                    <?php endif; ?>

                    <div style="padding: 1.5rem;">
                        <h3 style="color: var(--primary-dark); margin-bottom: 1rem; font-size: 1.1rem;">
                            <i class="fas fa-info-circle" style="color: var(--primary-light); margin-right: 0.4rem;"></i>
                            Sermon Details
                        </h3>

                        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.85rem;">
                            <li style="display: flex; align-items: flex-start; gap: 0.75rem; font-size: 0.95rem; color: #555;">
                                <i class="fas fa-user" style="color: var(--primary-light); width: 18px; margin-top: 2px;"></i>
                                <div>
                                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #999; margin-bottom: 2px;">Preacher</div>
                                    <strong><?php echo htmlspecialchars($sermon['preacher']); ?></strong>
                                </div>
                            </li>
                            <li style="display: flex; align-items: flex-start; gap: 0.75rem; font-size: 0.95rem; color: #555;">
                                <i class="fas fa-calendar" style="color: var(--primary-light); width: 18px; margin-top: 2px;"></i>
                                <div>
                                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #999; margin-bottom: 2px;">Date</div>
                                    <strong><?php echo $formatted_date; ?></strong>
                                </div>
                            </li>
                            <?php if (!empty($sermon['scripture_reference'])): ?>
                            <li style="display: flex; align-items: flex-start; gap: 0.75rem; font-size: 0.95rem; color: #555;">
                                <i class="fas fa-book-open" style="color: var(--primary-light); width: 18px; margin-top: 2px;"></i>
                                <div>
                                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #999; margin-bottom: 2px;">Scripture</div>
                                    <strong><?php echo htmlspecialchars($sermon['scripture_reference']); ?></strong>
                                </div>
                            </li>
                            <?php endif; ?>
                            <?php if (!empty($sermon['series'])): ?>
                            <li style="display: flex; align-items: flex-start; gap: 0.75rem; font-size: 0.95rem; color: #555;">
                                <i class="fas fa-layer-group" style="color: var(--primary-light); width: 18px; margin-top: 2px;"></i>
                                <div>
                                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #999; margin-bottom: 2px;">Series</div>
                                    <strong><?php echo htmlspecialchars($sermon['series']); ?></strong>
                                </div>
                            </li>
                            <?php endif; ?>
                        </ul>

                        <!-- Action buttons -->
                        <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php if (!empty($sermon['video_url'])): ?>
                            <a href="<?php echo htmlspecialchars($sermon['video_url']); ?>" target="_blank" rel="noopener" class="btn btn-primary" style="text-align: center;">
                                <i class="fas fa-play"></i> Watch Video
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($sermon['audio_url'])): ?>
                            <a href="<?php echo htmlspecialchars($sermon['audio_url']); ?>" target="_blank" rel="noopener" class="btn btn-secondary" style="text-align: center;">
                                <i class="fas fa-headphones"></i> Listen / Download
                            </a>
                            <?php endif; ?>
                            <a href="services.php#sermons" class="btn btn-secondary" style="text-align: center;">
                                <i class="fas fa-list"></i> All Sermons
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Share Card -->
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 1.5rem;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                    margin-bottom: 2rem;
                ">
                    <h3 style="color: var(--primary-dark); margin-bottom: 1rem; font-size: 1rem;">
                        <i class="fas fa-share-alt" style="color: var(--primary-light); margin-right: 0.4rem;"></i>
                        Share This Sermon
                    </h3>
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <?php
                        $share_url   = urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                        $share_title = urlencode($sermon['title'] . ' — ' . $sermon['preacher']);
                        ?>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>"
                           target="_blank" rel="noopener"
                           style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 6px; background: #1877f2; color: white; text-decoration: none; font-size: 0.9rem;">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
                           target="_blank" rel="noopener"
                           style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 6px; background: #1da1f2; color: white; text-decoration: none; font-size: 0.9rem;">
                            <i class="fab fa-twitter"></i> Twitter
                        </a>
                        <a href="https://wa.me/?text=<?php echo $share_title . '%20' . $share_url; ?>"
                           target="_blank" rel="noopener"
                           style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 6px; background: #25d366; color: white; text-decoration: none; font-size: 0.9rem;">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        <button onclick="copySermonLink()"
                                style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 6px; background: #6c757d; color: white; border: none; cursor: pointer; font-size: 0.9rem;">
                            <i class="fas fa-link"></i> <span id="copyBtnText">Copy Link</span>
                        </button>
                    </div>
                </div>

                <!-- Related Sermons -->
                <?php if (!empty($related_sermons)): ?>
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 1.5rem;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                ">
                    <h3 style="color: var(--primary-dark); margin-bottom: 1.25rem; font-size: 1rem;">
                        <i class="fas fa-bible" style="color: var(--primary-light); margin-right: 0.4rem;"></i>
                        More Sermons
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($related_sermons as $rel): ?>
                        <?php
                        // Resolve related sermon thumbnail
                        $rel_image = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&h=80&fit=crop';
                        if (!empty($rel['cover_image']) && file_exists($rel['cover_image'])) {
                            $rel_image = htmlspecialchars($rel['cover_image']);
                        }
                        $rel_date = function_exists('format_date') ? format_date($rel['sermon_date']) : date('M j, Y', strtotime($rel['sermon_date']));
                        ?>
                        <a href="sermon.php?id=<?php echo (int)$rel['id']; ?>"
                           style="display: flex; gap: 0.75rem; text-decoration: none; align-items: center; padding: 0.5rem; border-radius: 8px; transition: background 0.2s;"
                           onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='transparent'">
                            <img src="<?php echo $rel_image; ?>"
                                 alt="<?php echo htmlspecialchars($rel['title']); ?>"
                                 style="width: 70px; height: 50px; object-fit: cover; border-radius: 6px; flex-shrink: 0;"
                                 onerror="this.src='https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&h=80&fit=crop';">
                            <div style="min-width: 0;">
                                <div style="font-weight: 600; color: var(--primary-dark); font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($rel['title']); ?>
                                </div>
                                <div style="color: #888; font-size: 0.8rem; margin-top: 2px;">
                                    <?php echo htmlspecialchars($rel['preacher']); ?> · <?php echo $rel_date; ?>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </aside><!-- /RIGHT -->
        </div>
    </div>
</section>

<!-- ============================================================
     RESPONSIVE LAYOUT — collapse sidebar below tablet
     ============================================================ -->
<style>
@media (max-width: 900px) {
    .container > div[style*="grid-template-columns: 1fr 340px"] {
        grid-template-columns: 1fr !important;
    }
    aside {
        order: -1; /* Show info card above the media on mobile */
    }
}
</style>

<!-- ============================================================
     JAVASCRIPT — copy link helper
     ============================================================ -->
<script>
function copySermonLink() {
    const url = window.location.href;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function () {
            const btn = document.getElementById('copyBtnText');
            btn.textContent = 'Copied!';
            setTimeout(function () { btn.textContent = 'Copy Link'; }, 2000);
        });
    } else {
        // Fallback for older browsers
        const el = document.createElement('textarea');
        el.value = url;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        const btn = document.getElementById('copyBtnText');
        btn.textContent = 'Copied!';
        setTimeout(function () { btn.textContent = 'Copy Link'; }, 2000);
    }
}
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
