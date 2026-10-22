<article class="course-card">
    <a class="course-image" href="<?= e(url($course['path'])) ?>">
        <?php if ($course['image']): ?>
            <img src="<?= asset($course['image']) ?>" alt="" loading="lazy">
        <?php else: ?>
            <div class="card-art"><?= e($course['category']) ?></div>
        <?php endif; ?>
        <span class="tag"><?= e($course['category']) ?></span>
    </a>
    <div class="course-body">
        <h3><a href="<?= e(url($course['path'])) ?>"><?= e($course['title']) ?></a></h3>
        <p><?= e($course['audience']) ?></p>
        <div class="course-bottom">
            <span><?= e($course['duration']) ?></span>
            <a class="text-link" href="<?= e(url($course['path'])) ?>">Voir le programme <span>↗</span></a>
        </div>
    </div>
</article>
