// mod_hiveforum reactions - lightweight AJAX reaction toggling.
// Calls the mod_hiveforum_toggle_reaction external function (see classes/external/toggle_reaction.php).

import Ajax from 'core/ajax';
import Notification from 'core/notification';

const SELECTORS = {
    REACTION_BUTTON: '[data-action="hiveforum-react"]',
    REPLY_TRIGGER: '.hiveforum-reply-trigger',
    EDIT_TRIGGER: '.hiveforum-edit-trigger',
    DELETE_TRIGGER: '.hiveforum-delete-trigger',
};

const toggleReaction = (postid, reactiontype) => {
    return Ajax.call([{
        methodname: 'mod_hiveforum_toggle_reaction',
        args: {postid, reactiontype},
    }])[0];
};

const handleReply = (trigger) => {
    const postid = trigger.dataset.postid;
    const postContainer = trigger.closest('.hiveforum-post');
    const existingForm = postContainer.parentElement.querySelector(`.hiveforum-reply-form[data-parent="${postid}"]`);

    if (existingForm) {
        existingForm.remove();
        return;
    }

    // Basic reply form template.
    const sesskey = M.cfg.sesskey;
    const discussionid = new URLSearchParams(window.location.search).get('d');

    const formHtml = `
        <form method="post" action="${M.cfg.wwwroot}/mod/hiveforum/post.php"
              class="hiveforum-reply-form mt-3" data-parent="${postid}">
            <input type="hidden" name="discussion" value="${discussionid}">
            <input type="hidden" name="parent" value="${postid}">
            <input type="hidden" name="sesskey" value="${sesskey}">
            <textarea name="message" class="form-control" rows="3" placeholder="Votre réponse..." required></textarea>
            <div class="hiveforum-reply-actions mt-2 text-right">
                <button type="submit" class="hiveforum-btn hiveforum-btn-primary">Répondre</button>
            </div>
        </form>
    `;

    postContainer.insertAdjacentHTML('afterend', formHtml);
    postContainer.nextElementSibling.querySelector('textarea').focus();
};

const handleEdit = (trigger) => {
    const postid = trigger.dataset.postid;
    const postBody = trigger.closest('.hiveforum-post-body');
    const messageContainer = postBody.querySelector('.hiveforum-post-message');
    const existingForm = postBody.querySelector('.hiveforum-edit-form');

    if (existingForm) {
        existingForm.remove();
        messageContainer.style.display = 'block';
        return;
    }

    const currentMessage = messageContainer.dataset.rawmessage || messageContainer.innerText;
    const sesskey = M.cfg.sesskey;
    const strsave = M.util.get_string('savechanges', 'core');
    const strcancel = M.util.get_string('cancel', 'core');
    const strreply = M.util.get_string('reply', 'hiveforum');

    const formHtml = `
        <form method="post" action="${M.cfg.wwwroot}/mod/hiveforum/post.php" class="hiveforum-edit-form mt-2">
            <input type="hidden" name="edit" value="${postid}">
            <input type="hidden" name="sesskey" value="${sesskey}">
            <textarea name="message" class="form-control" rows="4" required>${currentMessage}</textarea>
            <div class="mt-2 text-right">
                <button type="submit" class="hiveforum-btn hiveforum-btn-primary">${strsave}</button>
                <button type="button" class="btn btn-secondary hiveforum-edit-cancel">${strcancel}</button>
            </div>
        </form>
    `;

    messageContainer.style.display = 'none';
    messageContainer.insertAdjacentHTML('afterend', formHtml);
    const form = postBody.querySelector('.hiveforum-edit-form');
    form.querySelector('textarea').focus();

    form.querySelector('.hiveforum-edit-cancel').addEventListener('click', () => {
        form.remove();
        messageContainer.style.display = 'block';
    });
};

const handleDelete = (trigger) => {
    const postid = trigger.dataset.postid;
    const confirmMessage = M.util.get_string('deleteconfirm', 'hiveforum');
    if (confirm(confirmMessage)) {
        const sesskey = M.cfg.sesskey;
        window.location.href = `${M.cfg.wwwroot}/mod/hiveforum/post.php?delete=${postid}&sesskey=${sesskey}`;
    }
};

export const init = () => {
    document.addEventListener('click', (e) => {
        const reactionBtn = e.target.closest(SELECTORS.REACTION_BUTTON);
        if (reactionBtn) {
            e.preventDefault();
            const postid = parseInt(reactionBtn.dataset.postid, 10);
            const reactiontype = reactionBtn.dataset.reactiontype;
            const countEl = reactionBtn.querySelector('.hiveforum-reaction-count');

            reactionBtn.classList.toggle('is-active');

            toggleReaction(postid, reactiontype).then((result) => {
                if (countEl) {
                    countEl.textContent = result.count;
                }
                return result;
            }).catch((error) => {
                reactionBtn.classList.toggle('is-active'); // Revert optimistic UI on failure.
                Notification.exception(error);
            });
            return;
        }

        const replyTrigger = e.target.closest(SELECTORS.REPLY_TRIGGER);
        if (replyTrigger) {
            e.preventDefault();
            handleReply(replyTrigger);
            return;
        }

        const editTrigger = e.target.closest(SELECTORS.EDIT_TRIGGER);
        if (editTrigger) {
            e.preventDefault();
            handleEdit(editTrigger);
            return;
        }

        const deleteTrigger = e.target.closest(SELECTORS.DELETE_TRIGGER);
        if (deleteTrigger) {
            e.preventDefault();
            handleDelete(deleteTrigger);
            return;
        }
    });
};
