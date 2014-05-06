<?php
class CommentController
{
	public function listView($page = 1)
	{
		$ret = Api::run(
			new ListCommentsJob(),
			[
				ListCommentsJob::PAGE_NUMBER => $page,
			]);

		$context = getContext();
		$context->transport->posts = $ret->entities;
		$context->transport->paginator = $ret;
	}

	public function addAction()
	{
		if (InputHelper::get('sender') == 'preview')
		{
			$comment = Api::run(
				new PreviewCommentJob(),
				[
					PreviewCommentJob::POST_ID => InputHelper::get('post-id'),
					PreviewCommentJob::TEXT => InputHelper::get('text')
				]);

			getContext()->transport->textPreview = $comment->getTextMarkdown();
		}

		Api::run(
			new AddCommentJob(),
			[
				AddCommentJob::POST_ID => InputHelper::get('post-id'),
				AddCommentJob::TEXT => InputHelper::get('text')
			]);
	}

	public function editView($id)
	{
		getContext()->transport->comment = CommentModel::findById($id);
	}

	public function editAction($id)
	{
		if (InputHelper::get('sender') == 'preview')
		{
			$comment = Api::run(
				new PreviewCommentJob(),
				[
					PreviewCommentJob::COMMENT_ID => $id,
					PreviewCommentJob::TEXT => InputHelper::get('text')
				]);

			getContext()->transport->textPreview = $comment->getTextMarkdown();
		}

		Api::run(
			new EditCommentJob(),
			[
				EditCommentJob::COMMENT_ID => $id,
				EditCommentJob::TEXT => InputHelper::get('text')
			]);
	}

	public function deleteAction($id)
	{
		$comment = Api::run(
			new DeleteCommentJob(),
			[
				DeleteCommentJob::COMMENT_ID => $id,
			]);
	}
}
