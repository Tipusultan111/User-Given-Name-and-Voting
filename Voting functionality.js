jQuery(document).ready(function($) {
  const postID = $('input[name="post_id"]').val();

  function loadNames() {
    $.get(nameObj.ajax_url, {
      action: 'get_names',
      post_id: postID
    }, function(res) {
      if (res.success) {
        let allHTML = '', topHTML = '';
        res.data.all.forEach((row, i) => {
          allHTML += `<tr><td>${i+1}</td><td>${row.user_name}</td><td>${row.given_name}</td>
          <td><button class="vote-btn" data-id="${row.id}">❤️</button></td></tr>`;
        });
        res.data.top.forEach((row, i) => {
          topHTML += `<tr><td>${i+1}</td><td>${row.given_name}</td><td>${row.votes}</td></tr>`;
        });
        $('#user-given-names-body').html(allHTML);
        $('#top-names-body').html(topHTML);
      }
    });
  }

  loadNames();

  $('#name-submit-form').on('submit', function(e) {
    e.preventDefault();
    $.post(nameObj.ajax_url, {
      action: 'submit_name',
      nonce: nameObj.nonce,
      post_id: postID,
      user_name: $('input[name="user_name"]').val(),
      given_name: $('input[name="given_name"]').val()
    }, function(res) {
      if (res.success) {
        $('#name-submit-form')[0].reset();
        loadNames();
      }
    });
  });

  $(document).on('click', '.vote-btn', function() {
    const id = $(this).data('id');
    $.post(nameObj.ajax_url, {
      action: 'vote_name',
      nonce: nameObj.nonce,
      id: id
    }, function(res) {
      if (res.success) {
        loadNames();
      } else {
        alert('You already voted!');
      }
    });
  });
});
